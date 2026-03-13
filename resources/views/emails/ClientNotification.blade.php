<!DOCTYPE html>
<html>
<head>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <p>Dear Dr {{ $details['client_name'] }},</p>

    <p>We wanted to keep you informed about the current status of your project.</p>

    <p><span style="font-weight:bold;">Project ID:</span> {{ $details['project_id'] }}</p>
    <p><span style="font-weight:bold;">Project Name:</span> {{ $details['project_title'] }}</p>
    <p><span style="font-weight:bold;">Project Status:</span> {{ $details['process_status'] }}</p>
    <p><span style="font-weight:bold;">Project Duration:</span> {{ $details['projectduration'] }}</p>
    <p><span style="font-weight:bold;">Budget:</span> {{ $details['budget'] }}</p>
    <p><span class="font-weight:bold;">Advance Payment:</span> {{ $details['advance_payment'] }} - {{ $details['advancePendingCheck'] }}</p>


    <p>For any queries regarding this project, please contact our <b >Project Manager: Manikandan </b></p>
    <p>Should you have any questions or need further assistance, feel free to reach out to us.</p>
    <p><b>Contact Information</b>: 9585051474</p>

    <p>Thank you for choosing Medics Research!</p>

    <p>Best regards,</p>
    <p><b class="strong-mail">{{$details['name']}}</b></p>
    <p><b class="strong-mail">www.MedicsResearch.com</b></p>
</body>
</html>
