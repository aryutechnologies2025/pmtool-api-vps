<!DOCTYPE html>
<html>

<head>
</head>

<body style="font-family: Arial, sans-serif; color: #333;">
    <p>Dear Dr {{ $details['client_name'] }},</p>

    <p>We are pleased to inform you that we have started working on your project.</p>

    <p><span style="font-weight:bold;">Project ID:</span> {{ $details['project_id'] }}</p>
    <p><span style="font-weight:bold;">Project Title:</span> {{ $details['project_requirement'] }}</p>
    <p><span style="font-weight:bold;">Project Duration:</span> {{ $details['projectduration'] }}</p>
    <p><span style="font-weight:bold;">Fee:</span> {{ $details['budget'] }}</p>
    <p><span style="font-weight:bold;">Payment Schedule:</span>Prepaid</p>
    <p><strong>Account Details:</strong></p>
    <p style="margin-left: 40px;">
        <strong>Account Name:</strong> MEDICS RESEARCH<br>
        <strong>Bank:</strong> HDFC<br>
        <strong>Account Number:</strong> 50200083203971<br>
        <strong>Account Type:</strong> Current<br>
        <strong>Branch:</strong> Samathanapuram, Tirunelveli<br>
        <strong>IFSC Code:</strong> HDFC0006889<br>
        <strong>UPI ID:</strong> jabarali2009-4@okaxis
    </p>

    <p>For any queries regarding this project, please contact our <b>Project Manager: Manikandan </b></p>
    <p>Should you have any questions or need further assistance, feel free to reach out to us.</p>
    <p><b>Contact Information</b>: 9585051474</p>

    <p>Thank you for choosing Medics Research!</p>

    <p>Best regards,</p>
    <p><b class="strong-mail">{{ $details['name'] }}</b></p>
    <p><b class="strong-mail">www.MedicsResearch.com</b></p>
   
</body>

</html>
