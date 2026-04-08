<!DOCTYPE html>
<html>

<head>
</head>

<body>
    <p>Dear Sir/Mam,</p>
    <p>I hope you are doing well.</p>
    <p>
        I would like to provide you with an update regarding the task associated with Project ID
        <strong>{{ $details['project_id'] }}</strong> has been assigned to you.
        Please take note of the following details:
    </p>
    <p>
        <b>Task Accepted:</b> <strong>{{$details['employee_name']}}</strong> has accepted the task.
    </p>
   
    <p>Looking forward to your updates on the progress.</p>
    <p>Best regards,</p>
    <p>{{ $details['employee_name'] }}<br>{{ $details['role'] }} <br> {{ $details['phone_number']}}</p>
</body>

</html>