@extends('errors.layout')

@section('codigo', '403')
@section('emoji', '🔒')
@section('titulo', 'Acceso restringido')
@section('mensaje', (isset($exception) && $exception->getMessage() ? $exception->getMessage() : 'No tienes permiso para ver esta página.'))
