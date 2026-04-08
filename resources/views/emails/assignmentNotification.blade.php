<!DOCTYPE html>
<html>
<head>
</head>
<body>
    <p>Dear {{ $details['name'] }},</p>
    <p>
        The <strong>{{ $details['project_id'] }}</strong> has been assigned as a  <strong>{{ $details['role'] }}</strong>.
    </p>
    <p>
       Please keep us updated on your progress.
    </p>
    <p>Best regards,</p>
    <p>{{ $details['createdBy'] }}<br>{{ $details['created_by_role']}}</p>
</body>
</html>


