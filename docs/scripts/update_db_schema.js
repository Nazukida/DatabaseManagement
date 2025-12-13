const mysql = require('mysql2');

const connection = mysql.createConnection({
    host: 'localhost',
    user: 'linli_master',
    password: 'admin888',
    database: 'dbms'
});

connection.connect(err => {
    if (err) {
        console.error('❌ Error connecting to database:', err.message);
        console.log('Please ensure the database users are set up by running "differentroot.sql" in your MySQL client.');
        process.exit(1);
    }
    console.log('✅ Connected to database as linli_master.');

    // SHA2('123', 256) hash for default password
    const defaultHash = 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3';

    const alterRestaurants = `ALTER TABLE restaurants ADD COLUMN PasswordHash VARCHAR(255) NOT NULL DEFAULT '${defaultHash}'`;
    const alterRiders = `ALTER TABLE riders ADD COLUMN PasswordHash VARCHAR(255) NOT NULL DEFAULT '${defaultHash}'`;

    connection.query(alterRestaurants, (err, results) => {
        if (err) {
            if (err.code === 'ER_DUP_FIELDNAME') {
                console.log('⚠️  PasswordHash column already exists in restaurants table.');
            } else {
                console.error('❌ Error updating restaurants table:', err.message);
            }
        } else {
            console.log('✅ Successfully added PasswordHash column to restaurants table. Default password: "123"');
        }

        connection.query(alterRiders, (err, results) => {
            if (err) {
                if (err.code === 'ER_DUP_FIELDNAME') {
                    console.log('⚠️  PasswordHash column already exists in riders table.');
                } else {
                    console.error('❌ Error updating riders table:', err.message);
                }
            } else {
                console.log('✅ Successfully added PasswordHash column to riders table. Default password: "123"');
            }
            
            connection.end();
        });
    });
});
