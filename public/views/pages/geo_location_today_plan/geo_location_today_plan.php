<!-- Content -->

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card bg-transparent shadow-none border-0 my-4">
        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Field Service /</span> Planned Today</h4>
        <div class="card-body p-0 pb-3">

            <div class="row mb-5">
                <div class="card card-action" id="card-block">
                    <div class="card-header">
                        <div class="card-action-title">
                            <span class="text-title display-5" data-bs-toggle="tooltip" data-bs-offset="0,4"
                                data-bs-placement="right" data-bs-custom-class="tooltip-primary" data-bs-html="true"
                                data-bs-original-title="" id="tooltip_locations_today_technicians_planned">
                                Technician Assignment Locations
                            </span>
                        </div>
                        <div class="card-action-element">
                            <ul class="list-inline mb-0">
                                <li class="list-inline-item">
                                    <button type="button" class="btn btn-icon btn-label-warning filter-offcanvas"
                                        data-bs-toggle="offcanvas" data-bs-target="#sidebarFilters"
                                        aria-controls="sidebarFilters">
                                        <a href="javascript:void(0);">
                                            <i class="tf-icons bx bx-filter-alt"></i>
                                        </a>
                                    </button>
                                </li>
                                <li class="list-inline-item">
                                    <button type="button" class="btn btn-icon btn-label-info card-collapsible">
                                        <a href="javascript:void(0);">
                                            <i class="tf-icons bx bx-chevron-up"></i>
                                        </a>
                                    </button>
                                </li>
                                <!-- <li class="list-inline-item">
                                    <button type="button" class="btn btn-icon btn-label-success card-expand">
                                        <a href="javascript:void(0);">
                                            <i class="tf-icons bx bx-fullscreen"></i>
                                        </a>
                                    </button>
                                </li> -->
                            </ul>
                        </div>
                    </div>
                    <div class="collapse show">
                        <div id="technician_jo_today">
                            <div class="alert alert-info d-flex" role="alert">
                                <span class="badge badge-center bg-info border-label-info p-3 me-2"><i
                                        class="bx bxs-info-circle fs-2"></i></span>
                                <div class="d-flex flex-column ps-1">
                                    <h6 class="alert-heading d-flex align-items-center mb-1">INFO</h6>
                                    <em>Please Select A Filter To See The Geo Locations.</em>
                                </div>
                            </div>
                            <!-- Leaflet-map     -->
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!--/ Content -->