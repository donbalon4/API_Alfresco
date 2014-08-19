@extends('master')
@section('content')
hola: {{($usuario)}}<br>
pass: {{$pass}}<br>
url: {{$url}}<BR>
<pre>
obj: {{dd($obj)}}<br>
</pre>
ticket: {{dd($ticket)}}<br>
error : {{$error}}
@stop