@extends('master')
@section('content')
hola: {{($usuario)}}<br>
pass: {{$pass}}<br>
url: {{$url}}<BR>
obj: {{dd($obj)}}<br>
ticket: {{dd($ticket)}}<br>
error : {{$error}}
@stop