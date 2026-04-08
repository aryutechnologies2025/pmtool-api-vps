<!DOCTYPE html>
<html>

<head>
</head>

<body>
    <p>Dear Dr {{ $details['client_name'] }},</p>

    <p>We are pleased to inform you that we have started working on your project.</p>
    <ul>
        <li><strong>Project ID:</strong> {{ $details['project_id'] }}</li>
        <li><strong>Project Name:</strong> {{ $details['project_title'] }}</li>
        <li><strong>Project Duration:</strong> {{ $details['duration'] }}</li>
        <li><strong>Project Amount:</strong> {{ $details['payment_amount'] }}</li>
        <li><strong>Project Status:</strong> {{ $details['payment_status'] }}</li>
    </ul>

   


    <p>Should you have any questions or need further assistance, feel free to reach out to us.</p>

    <p>Thank you for choosing Medics Research!</p>

    <p>Best regards,</p>
    <p>{{ $details['client_name'] }}</p>
    <p>{{ $details['contact_number'] }}</p>

</body>

</html>