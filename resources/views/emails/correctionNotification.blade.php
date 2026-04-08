<!DOCTYPE html>
<html>
<head>
</head>
<body>
    <p>Dear {{ $details['employee_name'] }},</p>
   
    <p>
        Please note that the <strong>{{ $details['status'] }}</strong> regarding  <strong>{{ $details['project_id'] }}</strong>has been assigned to {{ $details['assign_to'] }}. 
    </p>
    <p>
       Kindly provide updates on your progress.
    </p>
    <p>Best regards,</p>
    {{-- <p>{{ $details['employee_name'] }}<br>{{ $details['type'] }}</p> --}}
    <p>{{ $details['createdBy'] }}<br>{{ $details['role'] }}</p>
</body>
</html>


