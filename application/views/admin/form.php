<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Tables'), array('label' => 'Basic Tables', 'active' => TRUE)))); ?>

<div class="row alertrow">
    <div class="col-md-12">
    <button class="btn-close alertBox" data-bs-dismiss="alert">x</button>
        <div class="alert alert-success"><strong>Well done!</strong> You successfully read this important alert message.</div>
    </div>
</div>

<div class="row alertrow">
    <div class="col-md-12">
     <button class="btn-close alertBox" data-bs-dismiss="alert">x</button>
        <div class="alert alert-danger"><strong>Heads up!</strong> This alert needs your attention, but it's not super important.</div>
    </div>

</div>

<?php $this->load->view('admin/partials/module_header', array('title' => 'Add Form')); ?>


<div class="card admin-card">



    <div class="card-body">

        <form role="form" id="form1" method="post" class="validate">

            <div class="admin-field mb-3">
                <label class="form-label">Required Field + Custom Message</label>

                <input type="text" class="form-control" name="name" data-validate="required" data-message-required="This is custom message for required field." placeholder="Required Field" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">Email Field</label>

                <input type="text" class="form-control" name="email" data-validate="email" placeholder="Email Field" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">Input Min Field</label>

                <input type="text" class="form-control" name="min_field" data-validate="number,minlength[4]" placeholder="Numeric + Minimun Length Field" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">Input Max Field</label>

                <input type="text" class="form-control" name="max_field" data-validate="maxlength[2]" placeholder="Maximum Length Field" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">Numeric Field</label>

                <input type="text" class="form-control" name="number" data-validate="number" placeholder="Numeric Field" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">URL Field</label>

                <input type="text" class="form-control" name="url" data-validate="required,url" placeholder="URL" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">Credit Card Field</label>

                <input type="text" class="form-control" name="creditcard" data-validate="required,creditcard" placeholder="Credit Card" />
            </div>

            <div class="admin-field mb-3">
                <button type="submit" class="btn btn-primary">Validate</button>
                <button type="reset" class="btn">Reset</button>
            </div>

        </form>



    <hr>
    <form role="form" id="form22" method="post" class="validate">

            <div class="admin-field mb-3">
                <label class="form-label">Required Field + Custom Message</label>

                <input type="text" class="form-control" name="name" data-validate="required" data-message-required="This is custom message for required field." placeholder="Required Field" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">Email Field</label>

                <input type="text" class="form-control" name="email" data-validate="email" placeholder="Email Field" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">Input Min Field</label>

                <input type="text" class="form-control" name="min_field" data-validate="number,minlength[4]" placeholder="Numeric + Minimun Length Field" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">Input Max Field</label>

                <input type="text" class="form-control" name="max_field" data-validate="maxlength[2]" placeholder="Maximum Length Field" />
            </div>


            </div>

            <div class="admin-field mb-3">
                <button type="submit" class="btn btn-primary">Validate Two</button>
                <button type="reset" class="btn">Reset</button>
            </div>

        </form>




    </div>

