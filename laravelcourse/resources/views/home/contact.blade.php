@extends('layouts.app') 

@section('title', 'contact') 

@section('name', $name) 

@section('phone', $phone) 

@section('content')


<div class="container"> 

  <div class="row"> 

    <div class="col-lg-4 ms-auto"> 

      <p class="lead">{{$name}}</p> 

    </div> 

    <div class="col-lg-4 me-auto"> 

      <p class="lead">{{$phone}}</p> 

    </div> 

  </div> 

</div> 

@endsection 