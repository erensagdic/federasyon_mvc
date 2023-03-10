@extends('layouts.backend')
@section('css')
    <link rel="stylesheet" href="{{ asset('assets/js/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/js/plugins/datatables-buttons-bs5/css/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('assets/js/plugins/datatables-responsive-bs5/css/responsive.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/js/plugins/magnific-popup/magnific-popup.css') }}">
@endsection
@section('content')
    <!-- Hero -->
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Aktif Öğretmenler Listesi</h1>
                <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">Examples</li>
                        <li class="breadcrumb-item active" aria-current="page">Blank</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <!-- END Hero -->

    <!-- Page Content -->
    <div class="content">

        <div id="imageModal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl text-center">
                <div class="modal-content">
                    <div class="modal-body">
                        <img style="max-width: 500px" src="{{ asset('uploads/teacher_photos/teacher.jpg') }}"
                            class="img-responsive">
                    </div>
                </div>
            </div>
        </div>
        <table class="table table-bordered table-striped table-vcenter js-dataTable-buttons">
            <thead>
                <tr>
                    <th style="width: 150px">#</th>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>T.C Kimlik Numarası</th>
                    <th>Bağlı Kurum</th>
                    <th>Kayıt Tarihi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ogretmenler as $ogretmen)
                    <tr>
                        <td class="js-gallery" style="width: 150px">
                            <a class="img-link img-link-zoom-in img-lightbox"
                                href="{{ asset('uploads/teacher_photos/' . $ogretmen->photo->photo_path) }}">
                                <img class="img-fluid" width="150px"
                                    src="{{ asset('uploads/teacher_photos/' . $ogretmen->photo->photo_path) }}"
                                    alt="">
                            </a>
                        </td>
                        <td>{{ $ogretmen->ozel_id }}</td>
                        <td>{{ $ogretmen->ad . ' ' . $ogretmen->soyad }}</td>
                        <td>{{ $ogretmen->tc_kimlik }}</td>
                        <td class="text-{{ $ogretmen->kurum == null ? 'danger' : 'success' }}">
                            {{ $ogretmen->kurum == null ? 'Yok' : $ogretmen->kurum->kurum->unvan }}</td>
                        <td>{{ date('d/m/Y H:i:s', strtotime($ogretmen->created_at)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <!-- END Page Content -->
@endsection
@section('js')
    <script src="{{ asset('assets/js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/datatables-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/datatables-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/datatables-buttons/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/datatables-buttons-bs5/js/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/datatables-buttons-jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/datatables-buttons-pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/datatables-buttons-pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/datatables-buttons/buttons.print.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/datatables-buttons/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/aktif-ogretmen-list.js') }}"></script>
    <!-- Page JS Plugins -->
    <script src="{{ asset('assets/js/plugins/magnific-popup/jquery.magnific-popup.min.js') }}"></script>
    <!-- Page JS Helpers (Magnific Popup Plugin) -->
    <script>
        Dashmix.helpersOnLoad(['jq-magnific-popup']);
    </script>
@endsection
