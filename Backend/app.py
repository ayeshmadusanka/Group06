import logging
import pandas as pd
import numpy as np
from sqlalchemy import create_engine
from sklearn.decomposition import TruncatedSVD
from sklearn.metrics.pairwise import cosine_similarity
from flask import Flask, request, jsonify
from flask_cors import CORS  # Import flask_cors
from statsmodels.tsa.holtwinters import ExponentialSmoothing
import datetime

# ---------------------------
# Step 1: Configure Logging
# ---------------------------
log_file = "/home/ayeshmadusanka-model/htdocs/model.ayeshmadusanka.site/python-project/log.txt"
logging.basicConfig(
    filename=log_file,
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
)

def log_and_print(message):
    print(message)
    logging.info(message)

# ---------------------------
# Step 2: Connect to MySQL
# ---------------------------
try:
    db_config = {
        'user': 'breaduser',
        'password': 'l7ix8xOqF1xqv5ufM9se',
        'host': 'localhost',
        'port': 3306,
        'database': 'bread'
    }
    connection_string = (
        f"mysql+pymysql://{db_config['user']}:{db_config['password']}@"
        f"{db_config['host']}:{db_config['port']}/{db_config['database']}"
    )
    engine = create_engine(connection_string)
    log_and_print("Database connection established successfully.")
except Exception as e:
    logging.error("Error connecting to the database.", exc_info=True)
    raise

# ---------------------------
# Step 3: Define Data Loading and Recommendation Functions
# ---------------------------
def load_recommendation_data():
    """
    Loads latest order data and preprocesses it to create the user-item interaction matrix.
    """
    try:
        query = """
        SELECT o.user_id, oi.item_id, oi.quantity
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        """
        df = pd.read_sql(query, engine)
        df.fillna(0, inplace=True)
        df['quantity'] = df['quantity'].astype(float)
        user_item_matrix = df.pivot_table(
            index='user_id',
            columns='item_id',
            values='quantity',
            aggfunc='sum',
            fill_value=0
        )
        log_and_print(f"Latest recommendation data loaded: {df.shape[0]} rows.")
        return user_item_matrix
    except Exception as e:
        logging.error("Error loading recommendation data.", exc_info=True)
        raise

def recommend_items(user_id, user_item_matrix, latent_matrix, item_factors, top_n=3):
    """
    Generates top_n recommendations for a given user.
    Returns:
      - List of recommended item_ids.
    """
    try:
        if user_id not in user_item_matrix.index:
            return []
        
        user_idx = user_item_matrix.index.get_loc(user_id)
        user_latent = latent_matrix[user_idx]
        scores = np.dot(item_factors, user_latent)

        recommendations = pd.DataFrame({
            'item_id': user_item_matrix.columns,
            'score': scores
        }).sort_values(by='score', ascending=False)
        
        interacted_items = set(user_item_matrix.loc[user_id][user_item_matrix.loc[user_id] > 0].index)
        filtered = recommendations[~recommendations['item_id'].isin(interacted_items)]
        rec_list = filtered['item_id'].head(top_n).tolist()
        
        # If recommendations are fewer than top_n, add more items from the full list
        if len(rec_list) < top_n:
            for item in recommendations['item_id']:
                if item not in rec_list:
                    rec_list.append(item)
                if len(rec_list) >= top_n:
                    break
        
        return rec_list[:top_n]
    except Exception as e:
        logging.error(f"Error generating recommendations for user {user_id}.", exc_info=True)
        return []

# ---------------------------
# Step 4: Predict Daily Order Counts for a Selected Item using Exponential Smoothing
# ---------------------------
def predict_daily_orders_for_item(item_id, forecast_days=7):
    """
    Queries daily orders data for a selected item (by joining orders and order_items),
    fits an exponential smoothing model on the daily data and forecasts order counts 
    for the next forecast_days days.
    Returns:
      - A dictionary with historical daily data (last available week) and forecasted daily values.
    """
    try:
        query = f"""
        SELECT DATE(o.order_date) AS order_day, SUM(oi.quantity) AS order_count
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        WHERE oi.item_id = {item_id}
        GROUP BY DATE(o.order_date)
        ORDER BY order_day
        """
        df_orders = pd.read_sql(query, engine)
        if df_orders.empty:
            return {"historical": [], "forecast": []}
        
        df_orders['order_day'] = pd.to_datetime(df_orders['order_day'])
        df_orders.set_index('order_day', inplace=True)
        
        # Create a continuous daily date range
        start_date = df_orders.index.min()
        end_date = df_orders.index.max()
        full_index = pd.date_range(start=start_date, end=end_date, freq='D')
        daily_orders = df_orders.reindex(full_index, fill_value=0)['order_count']

        # Fit Exponential Smoothing on daily data with a seasonal component
        model = ExponentialSmoothing(
            daily_orders,
            trend='add',
            seasonal='add',
            seasonal_periods=7,
            initialization_method="estimated"
        )
        model_fit = model.fit(optimized=True)
        forecast = model_fit.forecast(forecast_days)

        # Prepare response: historical as last 7 days of available data
        historical = [{"date": str(idx.date()), "order_count": int(count)}
                      for idx, count in daily_orders[-7:].items()]
        forecast_list = [{"date": str(idx.date()), "predicted_order_count": int(round(val))}
                         for idx, val in forecast.items()]
        
        return {"historical": historical, "forecast": forecast_list}
    except Exception as e:
        logging.error("Error predicting daily orders for item.", exc_info=True)
        return {"historical": [], "forecast": []}

# ---------------------------
# Step 5: Predict Global Daily Order Counts using Exponential Smoothing
# ---------------------------
def predict_daily_orders_global(forecast_days=7):
    """
    Queries daily orders data (all orders), fits an exponential smoothing model on the daily data,
    and forecasts order counts for the next forecast_days days.
    Returns:
      - A dictionary with historical daily data (last available week) and forecasted daily values.
    """
    try:
        query = """
        SELECT DATE(order_date) AS order_day, COUNT(*) AS order_count
        FROM orders
        GROUP BY DATE(order_date)
        ORDER BY order_day
        """
        df_orders = pd.read_sql(query, engine)
        if df_orders.empty:
            return {"historical": [], "forecast": []}
        
        df_orders['order_day'] = pd.to_datetime(df_orders['order_day'])
        df_orders.set_index('order_day', inplace=True)
        
        # Create a continuous daily date range
        start_date = df_orders.index.min()
        end_date = df_orders.index.max()
        full_index = pd.date_range(start=start_date, end=end_date, freq='D')
        daily_orders = df_orders.reindex(full_index, fill_value=0)['order_count']

        # Fit Exponential Smoothing on daily data with a seasonal component
        model = ExponentialSmoothing(
            daily_orders,
            trend='add',
            seasonal='add',
            seasonal_periods=7,
            initialization_method="estimated"
        )
        model_fit = model.fit(optimized=True)
        forecast = model_fit.forecast(forecast_days)

        # Prepare response: historical as last 7 days and forecast for next 7 days
        historical = [{"date": str(idx.date()), "order_count": int(count)}
                      for idx, count in daily_orders[-7:].items()]
        forecast_list = [{"date": str(idx.date()), "predicted_order_count": int(round(val))}
                         for idx, val in forecast.items()]
        
        return {"historical": historical, "forecast": forecast_list}
    except Exception as e:
        logging.error("Error predicting global daily orders.", exc_info=True)
        return {"historical": [], "forecast": []}

# ---------------------------
# Step 6: Setup Flask API with CORS Enabled
# ---------------------------
application = Flask(__name__)
CORS(application)  # Enable CORS for all routes

@application.route('/recommendations/<int:user_id>', methods=['GET'])
def get_recommendations(user_id):
    """
    API endpoint to return recommendations for a given user_id.
    Recalculates the recommendation model with the latest data on every call.
    """
    try:
        # Load the latest data and recalculate recommendations on the fly
        user_item_matrix = load_recommendation_data()
        if user_item_matrix.empty or user_id not in user_item_matrix.index:
            return jsonify({"user_id": user_id, "recommendations": []}), 200

        n_components = min(20, user_item_matrix.shape[1])
        svd = TruncatedSVD(n_components=n_components, random_state=42)
        latent_matrix = svd.fit_transform(user_item_matrix)
        item_factors = svd.components_.T

        recs = recommend_items(user_id, user_item_matrix, latent_matrix, item_factors, top_n=3)
        response = {
            "user_id": user_id,
            "recommendations": recs
        }
        log_and_print(f"Recommendations for user {user_id}: {recs}")
        return jsonify(response), 200
    except Exception as e:
        logging.error(f"Error in API endpoint for user {user_id}.", exc_info=True)
        return jsonify({"error": "Internal server error"}), 500

@application.route('/predict_orders/<int:item_id>', methods=['GET'])
def predict_orders_item(item_id):
    """
    API endpoint that returns daily order count predictions for a selected item
    using exponential smoothing.
    """
    try:
        prediction = predict_daily_orders_for_item(item_id, forecast_days=7)
        log_and_print(f"Daily order prediction for item {item_id} generated.")
        return jsonify(prediction), 200
    except Exception as e:
        logging.error("Error in order prediction endpoint for item.", exc_info=True)
        return jsonify({"error": "Internal server error"}), 500

@application.route('/predict_orders_global', methods=['GET'])
def predict_orders_global_endpoint():
    """
    API endpoint that returns global daily order count predictions (all orders)
    using exponential smoothing.
    """
    try:
        prediction = predict_daily_orders_global(forecast_days=7)
        log_and_print("Global daily order prediction generated.")
        return jsonify(prediction), 200
    except Exception as e:
        logging.error("Error in global order prediction endpoint.", exc_info=True)
        return jsonify({"error": "Internal server error"}), 500

# This alias is needed by uWSGI to locate your Flask application.
application = application

if __name__ == '__main__':
    # Run the Flask app on all network interfaces on port 5000
    application.run(host="0.0.0.0", port=5000)
