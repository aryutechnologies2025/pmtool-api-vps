<!DOCTYPE html>
<html>

<head>
</head>

<body>
    <p>Dear Dr.{{ $details['client_name'] }},</p>

    <p>We kindly request you to make an advance payment for the project.</p>

    <ul>
        <li><strong class="strong-mail">Project ID:</strong> {{ $details['project_id'] }}</li>
        <li><strong class="strong-mail">Project Name:</strong> {{ $details['project_title'] }}</li>
        <li><strong class="strong-mail">Fee:</strong> {{ $details['budget'] }}</li>
        <li><strong class="strong-mail">Advance Payment:</strong> {{ $details['advance_payment'] }}  {{ $details['advancePendingCheck'] }}</li>
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

    </ul>

    <p>For any queries regarding this project, please contact our <b >Project Manager: Manikandan </b></p>
    <p>Should you have any questions or need further assistance, feel free to reach out to us.</p>
    <p><b>Contact Information</b>: 9585051474</p>

    <p>Thank you for choosing Medics Research!</p>
    <p >Best regards,</p>
    <p><b class="strong-mail">{{$details['name']}}</b></p>
    <p><b class="strong-mail">www.MedicsResearch.com</b></p>

</body>

</html>