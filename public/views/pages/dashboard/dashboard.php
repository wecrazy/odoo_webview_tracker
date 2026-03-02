<?php
// session_start();
?>

<!-- Content -->

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card bg-transparent shadow-none border-0 my-4">
        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Technicians Login</span></h4>
        <div class="card-body row p-0 pb-3">

            <div class="card" id="card-block">
                <div class="card-header flex-column flex-md-row">
                    <div class="dt-action-buttons text-end pt-3 pt-md-0">
                        <div class="dt-buttons">
                            <span class="display-6" id="last_update">
                                <!--  -->
                            </span>
                            <button class="dt-button btn btn-success btn-export-data m-2"><i class="fa-solid fa-download"></i> &nbsp; Excel</button>
                            <button class="dt-button btn btn-primary btn-refresh-data m-2"><i class="fa-solid fa-arrows-rotate"></i> &nbsp; Refresh</button>
                        </div>
                    </div>
                </div>
                <div class="card-datatable table-responsive">
                    <table class="datatables-technicians table border-top">
                        <thead>
                            <tr>
                                <th></th>
                                <th></th>
                                <th class="text-center">Technician</th>
                                <th class="text-center">Group</th>
                                <th class="text-center">Last Login</th>
                                <th class="text-center">Last Download</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">First Upload JO</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!--/ Content -->