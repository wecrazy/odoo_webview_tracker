<!-- Content -->

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card bg-transparent shadow-none border-0 my-4">
        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Field Service /</span> Assigned JO</h4>
        <div class="card-body p-0 pb-3">

            <!-- New JO Left -->
            <div class="row">
                <div class="card card-action mb-5" id="card-block">
                    <div class="card-header">
                        <div class="card-action-title">
                            <span class="text-title display-5" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                data-bs-placement="right" data-bs-custom-class="tooltip-primary" data-bs-html="true"
                                data-bs-original-title="<span>Contains Planned & Unplanned JO for Technicians</span>">
                                New JO Left
                            </span>
                        </div>
                        <div class="card-action-element">
                            <ul class="list-inline mb-0">
                                <li class="list-inline-item">
                                    <span class="display-6" id="last_update">
                                    </span>
                                </li>
                                <li class="list-inline-item">
                                    <div class="btn-group" role="group">
                                        <button id="export-excel" type="button"
                                            class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false">
                                            <i class="fa-solid fa-download"></i> &nbsp; Excel
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="export-excel">
                                            <a class="dropdown-item export-excel-new-jo-left" href="#">Report
                                                New JO Left For Technicians</a>
                                            <a class="dropdown-item export-excel-inconsistent-technicians" href="#">Report
                                                Inconsistent Technicians</a>
                                            <a class="dropdown-item export-excel-non-working-technicians" href="#">Report
                                                Non Working Technicians</a>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-inline-item">
                                    <button type="button" class="btn btn-icon btn-label-info card-collapsible">
                                        <a href="javascript:void(0);">
                                            <i class="tf-icons bx bx-chevron-up"></i>
                                        </a>
                                    </button>
                                </li>
                                <li class="list-inline-item">
                                    <button type="button" class="btn btn-icon btn-label-warning card-reload">
                                        <a href="javascript:void(0);">
                                            <i class="tf-icons bx bx-rotate-left scaleX-n1-rtl"></i>
                                        </a>
                                    </button>
                                </li>
                                <li class="list-inline-item">
                                    <button type="button" class="btn btn-icon btn-label-danger card-expand">
                                        <a href="javascript:void(0);">
                                            <i class="tf-icons bx bx-fullscreen"></i>
                                        </a>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="collapse show">
                        <div class="card-datatable table-responsive">
                            <table class="datatables-today-planned-jo table border-top">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th class="text-center">Technician</th>
                                        <th class="text-center">SAC Group</th>
                                        <th class="text-center">JO Planned On</th>
                                        <th class="text-center"><span
                                                class="badge bg-danger text-white badge-notifications">New</span>
                                            &nbsp;&nbsp; WO Number
                                        </th>
                                        <th class="text-center">Company</th>
                                        <th class="text-center">Task Type</th>
                                        <th class="text-center">SLA Deadline</th>
                                        <th class="text-center">Last Update Data On</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- / New JO Left -->

            <!-- Inconsistent Technicians -->
            <div class="row">
                <div class="card card-2 card-action mb-5" id="card-block2">
                    <div class="card-header">
                        <div class="card-action-title">
                            <span class="text-title text-warning display-5 tooltip-inconsistent-technicians" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                data-bs-placement="right" data-bs-custom-class="tooltip-primary" data-bs-html="true"
                                data-bs-original-title="">
                                Inconsistent Technicians
                            </span>
                        </div>
                        <div class="card-action-element">
                            <ul class="list-inline mb-0">
                                <li class="list-inline-item">
                                    <button type="button" class="btn btn-icon btn-label-info card-collapsible">
                                        <a href="javascript:void(0);">
                                            <i class="tf-icons bx bx-chevron-up"></i>
                                        </a>
                                    </button>
                                </li>
                                <li class="list-inline-item">
                                    <button type="button" class="btn btn-icon btn-label-danger card-expand">
                                        <a href="javascript:void(0);">
                                            <i class="tf-icons bx bx-fullscreen"></i>
                                        </a>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="collapse show">
                        <div class="card-datatable table-responsive" id="container-dt-inconsistent-technicians">
                            <!-- Dynamic table -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- / Inconsistent Technicians -->

            <!-- Non Working Technicians -->
            <div class="row">
                <div class="card card-3 card-action mb-5" id="card-block3">
                    <div class="card-header">
                        <div class="card-action-title">
                            <span class="text-title text-danger display-5 tooltip-non-working-technicians" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                data-bs-placement="right" data-bs-custom-class="tooltip-primary" data-bs-html="true"
                                data-bs-original-title="">
                                History of Non Working Technicians
                            </span>
                        </div>
                        <div class="card-action-element">
                            <ul class="list-inline mb-0">
                                <li class="list-inline-item">
                                    <button type="button" class="btn btn-icon btn-label-info card-collapsible">
                                        <a href="javascript:void(0);">
                                            <i class="tf-icons bx bx-chevron-up"></i>
                                        </a>
                                    </button>
                                </li>
                                <li class="list-inline-item">
                                    <button type="button" class="btn btn-icon btn-label-danger card-expand">
                                        <a href="javascript:void(0);">
                                            <i class="tf-icons bx bx-fullscreen"></i>
                                        </a>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="collapse show">
                        <div class="card-datatable table-responsive" id="container-dt-non-working-technicians">
                            <!-- Dynamic table -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- / Non Working Technicians -->


            <!-- Inactive Technician Has JO -->
            <!-- This is difficult coz must check on its ticket !!!!!! -->
            <!-- / Inactive Technician Has JO -->

        </div>
    </div>
</div>
<!--/ Content -->