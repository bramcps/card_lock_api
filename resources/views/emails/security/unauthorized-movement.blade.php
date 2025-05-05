@component('mail::message')
# Security Alert: Unauthorized Movement Detected

An unauthorized movement has been detected at:

@component('mail::panel')
**Door:** {{ $doorName }}
**Location:** {{ $doorLocation }}
**Time:** {{ $triggeredAt }}
@endcomponent

This alert has been logged in the system and requires investigation. Please review the security footage and access logs for this time period.

@component('mail::button', ['url' => $viewUrl])
View Alert Details
@endcomponent

This is an automated message from your RFID Door Access Security System.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
