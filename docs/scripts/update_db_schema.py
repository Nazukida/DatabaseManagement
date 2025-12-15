import mysql.connector
import sys

def update_schema():
    try:
        conn = mysql.connector.connect(
            host='localhost',
            user='linli_master',
            password='admin888',
            database='dbms'
        )
        
        if conn.is_connected():
            print('✅ Connected to database as linli_master.')
            cursor = conn.cursor()
            
            # SHA2('123', 256) hash
            default_hash = 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3'
            
            # Update restaurants table
            try:
                cursor.execute(f"ALTER TABLE restaurants ADD COLUMN PasswordHash VARCHAR(255) NOT NULL DEFAULT '{default_hash}'")
                print('✅ Successfully added PasswordHash column to restaurants table.')
            except mysql.connector.Error as err:
                if err.errno == 1060: # Duplicate column name
                    print('⚠️  PasswordHash column already exists in restaurants table.')
                else:
                    print(f"❌ Error updating restaurants table: {err}")

            # Update riders table
            try:
                cursor.execute(f"ALTER TABLE riders ADD COLUMN PasswordHash VARCHAR(255) NOT NULL DEFAULT '{default_hash}'")
                print('✅ Successfully added PasswordHash column to riders table.')
            except mysql.connector.Error as err:
                if err.errno == 1060:
                    print('⚠️  PasswordHash column already exists in riders table.')
                else:
                    print(f"❌ Error updating riders table: {err}")

            conn.commit()
            cursor.close()
            conn.close()
            
    except mysql.connector.Error as err:
        print(f"❌ Error connecting to database: {err}")
        print("Please ensure the database users are set up by running 'differentroot.sql'.")
        sys.exit(1)

if __name__ == "__main__":
    update_schema()
