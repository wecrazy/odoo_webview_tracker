<!-- Content -->

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card bg-transparent shadow-none border-0 my-4">
        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Field Service /</span> Planned Today</h4>
        <div class="card-body p-0 pb-3">

            <div class="row mb-5">
                <!-- Today JO Planned -->
                <div class="col-lg-8 col-md-12 mb-3">
                    <div class="card card-action" id="card-block">
                        <div class="card-header">
                            <div class="card-action-title">
                                <h3>Today's JO Plan</h3>
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
                                                class="btn btn-outline-success dropdown-toggle"
                                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa-solid fa-download"></i> &nbsp; Excel
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="export-excel">
                                                <a class="dropdown-item export-excel-engineers-planned-today"
                                                    href="#">Report
                                                    Engineers Planned Today</a>
                                                <a class="dropdown-item export-excel-engineers-over-planned"
                                                    href="#">Report
                                                    Engineers Planned > 40 JO</a>
                                                <a class="dropdown-item export-excel-engineers-late-upload-jo"
                                                    href="#">Report Engineers Offline / Late Upload JO</a>
                                                <a class="dropdown-item export-excel-engineers-unplanned-jo"
                                                    href="#">Report Engineers Not Have JO Plan For Today</a>
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
                                            <th class="text-center">WO Number</th>
                                            <th class="text-center">Stage</th>
                                            <th class="text-center">Company</th>
                                            <th class="text-center">Task Type</th>
                                            <th class="text-center">Timesheet Last Stop</th>
                                            <th class="text-center">SLA Deadline</th>
                                            <th class="text-center">Last Update Data On</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- / Today JO Planned -->

                <!-- Unplanned JO For Technicians -->
                <div class="col-lg-4 col-md-12">
                    <div class="card card-4 card-action" id="card-block4">
                        <div class="card-header">
                            <div class="card-action-title">
                                <span id="last_update4" class="text-title text-warning display-6" data-bs-toggle="tooltip"
                                    data-bs-offset="0,4" data-bs-placement="right"
                                    data-bs-custom-class="tooltip-primary" data-bs-html="true" data-bs-original-title="">
                                    Technicians Not Have JO Plan For Today
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
                                        <button type="button" class="btn btn-icon btn-label-danger card-expand4">
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
                                <table class="datatables-technicians-unplanned-today table border-top">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th class="text-center">Technicians</th>
                                            <th class="text-center">New JO Available</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- / Unplanned JO For Technicians -->
            </div>

            <!-- Engineers Over Planned JO -->
            <div class="row card card-2 card-action mb-5" id="card-block2">
                <div class="card-header">
                    <div class="card-action-title">
                        <span class="text-title text-danger display-5" data-bs-toggle="tooltip" data-bs-offset="0,4"
                            data-bs-placement="right" data-bs-custom-class="tooltip-primary" data-bs-html="true"
                            data-bs-original-title="" id="tooltip-over-plan-jo">
                            Engineers Over Planned JO
                        </span>
                    </div>
                    <div class="card-action-element">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <span class="display-6" id="last_update2">
                                </span>
                            </li>
                            <li class="list-inline-item">
                                <button type="button" class="btn btn-icon btn-label-info card-collapsible">
                                    <a href="javascript:void(0);">
                                        <i class="tf-icons bx bx-chevron-up"></i>
                                    </a>
                                </button>
                            </li>
                            <li class="list-inline-item">
                                <button type="button" class="btn btn-icon btn-label-danger card-expand2">
                                    <a href="javascript:void(0);">
                                        <i class="tf-icons bx bx-fullscreen"></i>
                                    </a>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="collapse show">
                    <div class="card-datatable text-nowrap">
                        <table class="datatables-over-planned-jo table table-bordered">
                            <thead>
                                <tr>
                                    <th rowspan="2"></th>
                                    <th rowspan="2">Technician</th>
                                    <th colspan="5" class="text-center">Total Planned JO</th>
                                    <th rowspan="2">Grand Total</th>
                                </tr>
                                <tr>
                                    <th>New</th>
                                    <th>Done</th>
                                    <th>Open Pending</th>
                                    <th>Cancel</th>
                                    <th>Verified</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Engineers Over Planned JO -->

            <!-- Late Upload JO by Engineers -->
            <div class="row card card-3 card-action mb-5" id="card-block3">
                <div class="card-header">
                    <div class="card-action-title">
                        <span class="text-title text-danger display-5" data-bs-toggle="tooltip" data-bs-offset="0,4"
                            data-bs-placement="right" data-bs-custom-class="tooltip-primary" data-bs-html="true"
                            data-bs-original-title="" id="tooltip-technicians-late-upload">
                            Engineers Offline / Late Upload JO
                        </span>
                    </div>
                    <div class="card-action-element">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <span class="display-6" id="last_update3">
                                </span>
                            </li>
                            <li class="list-inline-item">
                                <button type="button" class="btn btn-icon btn-label-info card-collapsible">
                                    <a href="javascript:void(0);">
                                        <i class="tf-icons bx bx-chevron-up"></i>
                                    </a>
                                </button>
                            </li>
                            <li class="list-inline-item">
                                <button type="button" class="btn btn-icon btn-label-danger card-expand3">
                                    <a href="javascript:void(0);">
                                        <i class="tf-icons bx bx-fullscreen"></i>
                                    </a>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="collapse show">
                    <div class="card-datatable text-nowrap">
                        <table class="datatables-late-upload-jo table table-bordered">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th>Technician</th>
                                    <th>WO Number</th>
                                    <th>Task Type</th>
                                    <th>Timesheet Last Stop</th>
                                    <th>Last Updated JO On</th>
                                    <th>ODOO Data Get On</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
            <!-- / Late Upload JO by Engineers -->

        </div>
    </div>
</div>
<!--/ Content -->