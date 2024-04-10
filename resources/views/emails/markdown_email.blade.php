@component('mail::message')
# Hello,

This is a Markdown email.

Here is some data passed from the constructor:
@foreach ($maildata as $key => $value)
- {{ $key }}: {{ $value }}
@endforeach

Thanks,<br>
{{ config('app.name') }}
@endcomponent