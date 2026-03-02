<!-- Content -->

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card bg-transparent shadow-none border-0 my-4">
        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Field Service /</span> Current Month - Planned Date</h4>
        <div class="card-body row p-0 pb-3">

            <div class="card card-action mb-5" id="card-block">
                <div class="card-header">
                    <div class="card-action-title"><h3>Today's JO Plan</h3></div>
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
                                        <a class="dropdown-item export-excel-engineers-planned-today" href="#">Report Engineers Planned Today</a>
                                        <!-- <a class="dropdown-item disabled" href="#">Report Engineers Without JO</a>
                                        <a class="dropdown-item disabled" href="#">Report Engineers Planned > 40 JO</a> -->
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
    </div>
</div>
<!--/ Content -->