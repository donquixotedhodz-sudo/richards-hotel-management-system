-- Update existing bookings to associate them with correct user_id based on email matching
-- This fixes the issue where legacy bookings have user_id as NULL

UPDATE bookings b 
INNER JOIN users u ON b.customer_email = u.email 
SET b.user_id = u.id 
WHERE b.user_id IS NULL;

-- Verify the update
SELECT 
    b.id as booking_id,
    b.customer_name,
    b.customer_email,
    b.user_id,
    u.first_name,
    u.last_name,
    u.email as user_email
FROM bookings b
LEFT JOIN users u ON b.user_id = u.id
ORDER BY b.id;