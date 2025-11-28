"""
Minimal Flask test for Railway
"""
import os
from flask import Flask, jsonify

app = Flask(__name__)

@app.route('/api/health', methods=['GET'])
def health():
    return jsonify({
        'status': 'healthy',
        'service': 'eye_tracking_test',
        'version': '1.0'
    }), 200

@app.route('/', methods=['GET'])
def index():
    return jsonify({
        'message': 'Eye Tracking Service Running',
        'port': os.getenv('PORT', '5000')
    })

if __name__ == '__main__':
    PORT = int(os.environ.get('PORT', 5000))
    print(f"🚀 Starting minimal test service on port {PORT}")
    app.run(host='0.0.0.0', port=PORT, debug=False)
