import mysql.connector
from datetime import date
import sys

def get_db_connection():
    """Establish connection to the same MySQL database used by PHP."""
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="root", # Match your config.php settings
            password="", 
            database="hotel_db"
        )
        return conn
    except mysql.connector.Error as err:
        print(f"Error connecting to database: {err}")
        sys.exit(1)

def send_checkout_alerts(conn):
    """Automated Email Alerts for guests checking out today."""
    print("--- RUNNING AUTOMATED CHECKOUT ALERTS ---")
    cursor = conn.cursor(dictionary=True)
    today = date.today()
    
    # Complex JOIN to get guest details and their reservations checking out today
    query = """
        SELECT r.reservation_id, g.first_name, g.last_name, g.email, rm.room_number
        FROM reservations r
        JOIN guests g ON r.guest_id = g.guest_id
        JOIN rooms rm ON r.room_id = rm.room_id
        WHERE r.check_out_date = %s AND r.status IN ('Checked-In', 'Checked-Out')
    """
    cursor.execute(query, (today,))
    checkouts = cursor.fetchall()
    
    if not checkouts:
        print(f"No checkouts scheduled for {today}.")
        return

    for guest in checkouts:
        # In a real-world scenario, you would use smtplib to send a real email here.
        print(f"Drafting Email to: {guest['email']}")
        print(f"Subject: Thank you for staying at Grand Soleil, {guest['first_name']}!")
        print(f"Body: Dear {guest['first_name']} {guest['last_name']},\n"
              f"We hope you enjoyed your stay in Room {guest['room_number']}. "
              f"Please stop by the front desk to settle your final invoice (Reservation #{guest['reservation_id']}).\n")
        print("-" * 40)
    
    cursor.close()

def generate_monthly_revenue_report(conn):
    """Complex math calculations: Aggregating Monthly Revenue Data."""
    print("--- RUNNING MONTHLY REVENUE ANALYTICS ---")
    cursor = conn.cursor(dictionary=True)
    
    # Calculate Room Revenue
    room_query = """
        SELECT COALESCE(SUM(DATEDIFF(r.check_out_date, r.check_in_date) * rm.rate_per_night), 0) AS total_room_rev
        FROM reservations r
        JOIN rooms rm ON rm.room_id = r.room_id
        WHERE MONTH(r.check_in_date) = MONTH(CURDATE()) 
          AND YEAR(r.check_in_date) = YEAR(CURDATE())
          AND r.status NOT IN ('Cancelled', 'No-Show')
    """
    cursor.execute(room_query)
    room_rev = cursor.fetchone()['total_room_rev']
    
    # Calculate Service Orders Revenue (applying discounts)
    service_query = """
        SELECT COALESCE(SUM(o.quantity * o.unit_price * (1 - o.discount_pct/100)), 0) AS total_service_rev
        FROM room_service_orders o
        WHERE MONTH(o.ordered_at) = MONTH(CURDATE()) 
          AND YEAR(o.ordered_at) = YEAR(CURDATE())
          AND o.status != 'Cancelled'
    """
    cursor.execute(service_query)
    service_rev = cursor.fetchone()['total_service_rev']
    
    # Complex Calculation: Total Revenue with Tax and Service Charge applied
    subtotal = float(room_rev) + float(service_rev)
    tax = subtotal * 0.12      # 12% VAT
    service_charge = subtotal * 0.10 # 10% Service Charge
    grand_total = subtotal + tax + service_charge
    
    print(f"Revenue Report for {date.today().strftime('%B %Y')}")
    print(f"Room Revenue:    ₱{float(room_rev):,.2f}")
    print(f"Service Revenue: ₱{float(service_rev):,.2f}")
    print(f"Subtotal:        ₱{subtotal:,.2f}")
    print(f"VAT (12%):       ₱{tax:,.2f}")
    print(f"Service (10%):   ₱{service_charge:,.2f}")
    print(f"GRAND TOTAL:     ₱{grand_total:,.2f}\n")
    
    cursor.close()

if __name__ == "__main__":
    db_conn = get_db_connection()
    send_checkout_alerts(db_conn)
    generate_monthly_revenue_report(db_conn)
    db_conn.close()