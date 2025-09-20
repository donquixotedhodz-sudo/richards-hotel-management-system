# Email Notification System

This folder contains the email notification system for the RHMS Hotel Management System.

## Files

### BookingNotificationService.php
The main service class that handles sending booking-related emails.

**Features:**
- Send booking confirmation emails
- Send booking cancellation emails
- Professional HTML email templates
- Integration with existing PHPMailer configuration

**Methods:**
- `sendBookingConfirmation($bookingId)` - Sends confirmation email to customer
- `sendBookingCancellation($bookingId)` - Sends cancellation email to customer

### test_email.php
A test file to verify email functionality without affecting actual bookings.

**Usage:**
1. Access via browser: `http://localhost/rhms-final/email-notification/test_email.php`
2. The test will use sample booking data from your database
3. **Warning:** This sends actual emails, so use test data only

## Integration

The email notification system is integrated into the admin booking management system:

- **File:** `admin/bookings.php`
- **Trigger:** When admin clicks "Confirm" or "Cancel" booking buttons
- **Behavior:** 
  - Updates booking status in database
  - Sends appropriate email to customer
  - Shows success/failure message to admin

## Email Templates

Both confirmation and cancellation emails include:
- Professional HTML design
- Complete booking details
- Hotel branding and contact information
- Responsive layout for mobile devices

### Confirmation Email Features:
- Green color scheme (success theme)
- Complete booking details
- Payment confirmation
- Check-in instructions
- Contact information

### Cancellation Email Features:
- Red color scheme (cancellation theme)
- Cancelled booking details
- Refund information
- Apology message
- Contact information for assistance

## Configuration

The email service uses the same SMTP configuration as the existing OTP system:
- **SMTP Server:** smtp.gmail.com
- **Port:** 587
- **Security:** STARTTLS
- **From Address:** richardshotelmanagement@gmail.com

## Error Handling

- All email sending attempts are logged
- Database errors are caught and logged
- User-friendly success/failure messages
- Graceful degradation (booking still processes if email fails)

## Testing

Before deploying to production:
1. Test with sample booking data
2. Verify email delivery
3. Check email formatting on different devices
4. Test with various booking scenarios (different room types, durations, etc.)

## Future Enhancements

Potential improvements:
- Email templates for other booking statuses (checked-in, checked-out)
- Email preferences for customers
- Email delivery tracking
- Multiple language support
- Email scheduling for reminders