<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Rooms', 'url' => ADMIN_URL.'portfolio'), array('label' => $catData['cat_name'].' '. $this->moduleName, 'active' => TRUE)))); ?>

<?php if($alert=="success") { ?>
<div class="row alertrow">
    <div class="col-md-12">
        <button class="btn-close alertBox" data-bs-dismiss="alert">x</button>
        <div class="alert alert-success"><strong>Success!</strong> <?php echo rtrim($this->moduleName,'s');?> added successfully.</div>
    </div>
</div>
<?php } if($alert=="deletesuccess") { ?>
<div class="row alertrow">
    <div class="col-md-12">
        <button class="btn-close alertBox" data-bs-dismiss="alert">x</button>
        <div class="alert alert-success"><strong>Success!</strong> <?php echo rtrim($this->moduleName,'s');?> deleted successfully.</div>
    </div>
</div>
<?php } if($alert=="deleteerror") { ?>
<div class="row alertrow">
    <div class="col-md-12">
        <button class="btn-close alertBox" data-bs-dismiss="alert">x</button>
        <div class="alert alert-danger"><strong>Error!!</strong> Error occurred while deleting the record, please try again.</div>
    </div>
</div>
<?php } if($alert=="editsuccess") { ?>
<div class="row alertrow">
    <div class="col-md-12">
        <button class="btn-close alertBox" data-bs-dismiss="alert">x</button>
        <div class="alert alert-success"><strong>Success!</strong> <?php echo rtrim($this->moduleName,'s');?> updated successfully.</div>
    </div>
</div>
<?php } if($alert=="error") { ?>
<div class="row alertrow">
    <div class="col-md-12">
        <button class="btn-close alertBox" data-bs-dismiss="alert">x</button>
        <div class="alert alert-danger"><strong>Error!!</strong> Error occurred while saving the record, please try again.</div>
    </div>
</div>
<?php } ?>


<?php $this->load->view('admin/partials/module_header', array('title' => $catData['cat_name'].' '.$this->moduleName)); ?>



<div class="row" style="min-height:400px;">
    <div class="col-md-12">
        <button  id="deleteAllRecords" class="btn btn-outline-secondary admin-btn-icon admin-action-icon" type="button">
            Delete Selected
            <i class="bi bi-trash"></i>
        </button>

        <button  class="btn btn-outline-secondary admin-btn-icon admin-action-icon" type="button" onclick="javascript:window.location='<?php echo base_url('manage/'.$this->controller.'/control/'.$cat_id);?>'">
            Add Single <?php echo rtrim($this->moduleName,'s');?>
            <i class="bi bi-plus-circle"></i>
        </button>
        <button  class="btn btn-outline-secondary admin-btn-icon admin-action-icon" type="button" onclick="javascript:window.location='<?php echo base_url('manage/'.$this->controller.'/upload-multiple/'.$cat_id);?>'">
            Add Multiple <?php echo $this->moduleName;?>
            <i class="bi bi-plus-circle"></i>
        </button>

        <span class="selectPerPage float-end"><br/>Records Per Page: <select class="admin-native-select admin-input-sm" name="per_page" id="per_page">
            <option value="0" <?php echo ((int)$this->per_page===0) ? ' selected="selected" ' : '';?>>All</option>
            <?php
            for($i=10;$i<=100;$i+=10)
            {
                echo '<option value="'.$i.'"';
                echo ((int)$this->per_page===$i) ? ' selected="selected" ' : '';
                echo '>'.$i.'</option>';
            }
            ?>
            </select></span>
        <br clear="all" />
        <hr />

           <form class="admin-filter-form float-end">
                  <input class="admin-input-md admin-input-wide aplha" type="text" value="<?php echo ($keywords!="-") ? $keywords : "" ;?>" name="search_keywords" id="search_keywords" placeholder="Search keywords">

                  <select autocomplete="off"  class=" admin-native-select admin-input-md" name="search_status" id="search_status">
                  <option value="-">Select Status</option>
                  <option value="Enable" <?php if(isset($status)&&$status=="Enable"){ echo ' selected="selected" ';} ?>>Enable</option>
                  <option value="Disable" <?php if(isset($status)&&$status=="Disable"){ echo ' selected="selected" ';} ?>>Disable</option>
                  </select>
             </form>

        <br clear="all" />

        <hr />
        <form action="<?php echo ADMIN_URL.$this->controller;?>/deleteall" method="post" name="multiDel" id="multiDel">
        <table id="table-<?php echo $this->controller;?>" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th><input type="checkbox" id="all-checkbox" name="all-checkbox" autocomplete="off"></th>
                    <th  class="hideCol d-none d-md-table-cell"><a href="<?php echo base_url('manage/'.$this->controller.'/index/'.$cat_id.'/'.$this->pKey.'/'.$order.'/'.$status.'/'.$keywords.'/'.$page_numb);?>">ID</a></th>
                    <th class="hideCol"><a href="<?php echo base_url('manage/'.$this->controller.'/index/'.$cat_id.'/'.$this->colPrefix.'image/'.$order.'/'.$status.'/'.$keywords.'/'.$page_numb);?>">Image</a></th>
                    <th><a href="<?php echo base_url('manage/'.$this->controller.'/index/'.$cat_id.'/'.$this->colPrefix.'name/'.$order.'/'.$status.'/'.$keywords.'/'.$page_numb);?>">Name</a></th>

                    <th><a href="<?php echo base_url('manage/'.$this->controller.'/index/'.$cat_id.'/'.$this->tStatus.'/'.$order.'/'.$status.'/'.$keywords.'/'.$page_numb);?>">Status</a></th>
                    <th class="hideCol d-none d-md-table-cell"><a href="<?php echo base_url('manage/'.$this->controller.'/index/'.$cat_id.'/'.$this->colPrefix.'added/'.$order.'/'.$status.'/'.$keywords.'/'.$page_numb);?>">Created On</a></th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php
              if(count($records)>0)
              {
                foreach($records as $c)
                {
                    ?>
              <tr id="<?php echo $this->controller.'-'.$c[$this->pKey];?>">
                  <td><input name="records[]" autocomplete="off" class="cselect" value="<?php echo $c[$this->pKey];?>" type="checkbox" /></td>
                 <td class="hideCol d-none d-md-table-cell"><?php echo $c[$this->pKey];?></td>

                <td class="hideCol">
                  <?php echo image_thumb(
                      './assets/frontend/images/'.$this->controller.'/'.$c[$this->colPrefix.'image'],
                      0, 80, 'webp', 'square', TRUE,
                      array('placeholder' => TRUE, 'group' => 'gallery')
                  ); ?>

                  </td>



                  <td><a href="<?php echo base_url('manage/'.$this->controller.'/control/edit/'.$c[$this->pKey]);?>"><?php echo $c[$this->colPrefix.'name'];?></a></td>
                  <td><a class="changestatus" href="javascript:void(0);" data-controller="<?php echo $this->controller;?>" id="statusID<?php echo $c[$this->pKey];?>"><?php echo $c[$this->tStatus];?></a></td>
                  <td class="hideCol d-none d-md-table-cell"><?php echo date(ADMIN_DATETIME_FORMAT, strtotime($c[$this->colPrefix.'added']));?></td>
                  <td>



                    <a class="admin-action-icon font16" href="<?php echo base_url('manage/'.$this->controller.'/control/'.$c[$this->colPrefix.'cat_id'].'/'.$c[$this->pKey]);?>">
                    <i class="bi bi-pencil"></i></a>

                    <a class="admin-action-icon font16 delitem" href="javascript:void(0);" data-controller="<?php echo $this->controller;?>" id="recordID<?php echo $c[$this->pKey];?>">
                    <i class="bi bi-x-circle"></i></a>

                  </td>
                </tr>

             <?php
                }
              }
              else{ ?>
                <tr><td colspan="7">Sorry! No Records Found.</td></tr>
              <?php
              }
              ?>

            </tbody>
        </table>
        </form>
       <?php
 $total_pages = ((int) $per_page === 0 )  ? '1' : ceil($total_rows/$per_page);
 $current_page = ((int) $per_page === 0 )  ? '1' : ceil($page_numb/$per_page)+1;
 if($total_pages=="1")
 {
    $showing_from = 1;
    $showing_to = $total_rows;
 }
 else if($total_pages==$current_page)
 {
    $showing_from = ($per_page*($current_page-1))+1;
    $showing_to = $total_rows;
 }
 else{
    $showing_from = ($per_page*($current_page-1))+1;
    $showing_to = $per_page*$current_page;
 }
 if($total_rows>0)
 {
?>
Showing <?php echo $showing_from;?> to <?php echo $showing_to;?> of <?php echo $total_rows;?> Record<?php echo ((int)$total_rows>1)? 's' : '';?> (Total <?php echo $total_pages;?> Page<?php echo ((int)$total_pages>1)? 's' : '';?>) <?php } echo (isset($paginate)&&$paginate!="") ? $paginate : '';?>

    </div>


</div>

<script type="text/javascript">
$(function(){

    $("#search_status").on('change',function(){
        var search_keywords = $("#search_keywords").val().trim();
        search_keywords = (search_keywords=="") ? "-" : encodeURI(search_keywords);
        window.location = "<?php echo base_url('manage/'.$this->controller.'/index/'.$cat_id.'/'.$sortby.'/'.(($order=="ASC") ? 'DESC' : 'ASC'));?>/"+$("#search_status").val()+"/"+search_keywords;
    });

    $("#search_keywords").keypress(function(event) {
        if (event.keyCode == 13) {
            var search_keywords = $("#search_keywords").val().trim();
        search_keywords = (search_keywords=="") ? "-" : encodeURI(search_keywords);
            window.location = "<?php echo base_url('manage/'.$this->controller.'/index/'.$cat_id.'/'.$sortby.'/'.(($order=="ASC") ? 'DESC' : 'ASC'));?>/"+$("#search_status").val()+"/"+search_keywords;
            return false;
        }
    });
    var req = null;
    $('#table-<?php echo $this->controller;?>').tableDnD({
        onDrop: function(table, row) {
            if(req!=null) req.abort();
            req = $.post('<?php echo ADMIN_URL.$this->controller.'/sortrecords';?>',$.tableDnD.serialize(),function(d){

            });
        }
    });

});

</script>

