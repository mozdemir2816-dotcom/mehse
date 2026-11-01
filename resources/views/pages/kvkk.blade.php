@extends('layouts.yasal-sayfa')

@section('baslik', 'KVKK Aydınlatma Metni')
@section('aciklama', 'Mehse İSG kişisel verilerin korunması ve işlenmesine ilişkin aydınlatma metni (6698 sayılı KVKK).')

@section('icerik')
    {{-- Metnin kendisi başvuru modalıyla ortak: resources/views/yasal/kvkk.blade.php --}}
    @include('yasal.kvkk')
@endsection
