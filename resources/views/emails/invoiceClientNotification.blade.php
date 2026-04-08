<!DOCTYPE html>
<html>

<head>
</head>

<body>
    <p>Dear {{ $details['client_name'] }},</p>

    <p>I hope this message finds you well.</p>
    <p>Please find attached the invoice <strong> {{ $details['invoice_number'] }}</strong> for the project <strong> {{ $details['project_title'] }} </strong>,
         covering the agreed-upon services/deliverables for the period <strong> {{ $details['start_date'] }}</strong> to <strong> {{ $details['end_date'] }}</strong>.
          Below are the key details for your reference:</p>

    <ul>
        <li><strong>Project ID:</strong> {{ $details['project_id'] }}</li>
        <li><strong>Project Name:</strong> {{ $details['project_title'] }}</li>
        
        <li><strong>Invoice Number:</strong> {{ $details['invoice_number'] }}</li>
        <li><strong>Invoice Date:</strong> {{ $details['invoice_date'] }}</li>
        <li><strong>Budget:</strong> {{ $details['budget'] }}</li>
        <li><strong>Advance Payment:</strong> {{ $details['advance_pending'] }}</li>
        <li><strong>Partial Payment:</strong> {{ $details['partial_payment_pending'] }}</li>
        <li><strong>Final Payment:</strong> {{ $details['final_payment_pending'] }}</li>
    </ul>

    
    <p>For any queries regarding this project, please contact our <strong>Project Manager: Manikandan </strong></p>
    <p>Should you have any questions or need further assistance, feel free to reach out to us.</p>
    <p><strong>Contact Information</strong>: 9585051474</p>

    <p>Thank you for choosing Medics Research!</p>

    <p>Best regards,</p>
    <p><strong>Medics Research</strong></p>
    

</body>

</html>