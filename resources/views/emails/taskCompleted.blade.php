<!DOCTYPE html>
<html>

<head>
</head>

<body>
    <p>Dear Sir/Mam,</p>
    
    <p>
        The status of
        <strong>{{ $details['project_id'] }}</strong> is {{$details['status']}}
    </p>
   
   
    <p>Thank You</p>
    <p>Best regards,</p>
    <p>{{ $details['employee_name'] }}</p>
    <p>{{ $details['role'] }}</p>
</body>

</html>