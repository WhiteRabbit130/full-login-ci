    <!-- jQuery 2.1.3 -->
    <script src="<?php echo base_url('assets/plugins/jQuery/jQuery-2.1.3.min.js');?>"></script>
    <!-- jQuery UI 1.11.2 -->
    <script src="<?php echo base_url('assets/plugins/jquery-ui-1.11.4/jquery-ui.min.js');?>" type="text/javascript"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
      $.widget.bridge('uibutton', $.ui.button);
    </script>
    <!-- Bootstrap 3.3.2 JS -->
    <script src="<?php echo base_url('assets/bootstrap/js/bootstrap.min.js')?>" type="text/javascript"></script>

    <!-- Bootstrap Validator -->
    <script src="<?php echo base_url('assets/plugins/validator/js/formValidation.js');?>" type="text/javascript"></script> 
    <script src="<?php echo base_url('assets/plugins/validator/js/framework/bootstrap.js');?>" type="text/javascript"></script> 

    <!-- Morris.js charts -->
    <script src="<?php echo base_url('assets/plugins/raphael/js/raphael-min.js');?>"></script>
    <script src="<?php echo base_url('assets/plugins/morris/morris.min.js');?>" type="text/javascript"></script>
    <!-- Sparkline -->
    <script src="<?php echo base_url('assets/plugins/sparkline/jquery.sparkline.min.js');?>" type="text/javascript"></script>
    <!-- jvectormap -->
    <script src="<?php echo base_url('assets/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js');?>" type="text/javascript"></script>
    <script src="<?php echo base_url('assets/plugins/jvectormap/jquery-jvectormap-world-mill-en.js');?>" type="text/javascript"></script>
    <!-- jQuery Knob Chart -->
    <script src="<?php echo base_url('assets/plugins/knob/jquery.knob.js');?>" type="text/javascript"></script>
    <!-- daterangepicker -->
    <script src="<?php echo base_url('assets/plugins/daterangepicker/daterangepicker.js');?>" type="text/javascript"></script>
    <!-- datepicker -->
    <script src="<?php echo base_url('assets/plugins/datepicker/bootstrap-datepicker.js');?>" type="text/javascript"></script>
    <!-- Bootstrap WYSIHTML5 -->
    <script src="<?php echo base_url('assets/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js');?>" type="text/javascript"></script>
    <!-- Slimscroll -->
    <script src="<?php echo base_url('assets/plugins/slimScroll/jquery.slimscroll.min.js');?>" type="text/javascript"></script>
    <!-- FastClick -->
    <script src='<?php echo base_url('assets/plugins/fastclick/fastclick.min.js');?>'></script>
    <!-- AdminLTE App -->
    <script src="<?php echo base_url('assets/dist/js/app.min.js');?>" type="text/javascript"></script> 
    <!-- DATA TABES SCRIPT -->
    <script src="<?php echo base_url('assets/plugins/datatables/jquery.dataTables.js');?>" type="text/javascript"></script>
    <script src="<?php echo base_url('assets/plugins/datatables/dataTables.bootstrap.js');?>" type="text/javascript"></script>
    <script src="<?php echo base_url('assets/plugins/ckeditor/ckeditor.js');?>"></script>
  

    <script type="text/javascript">
      $(function () {
        $('#SearchTable').dataTable({
          "bPaginate": true,
          "bLengthChange": true,
          "bFilter": true,
          "bSort": true,
          "bInfo": true,
          "bAutoWidth": true
        });
      });
    </script>

     <!-- iCheck -->
    <script src="<?php echo base_url('assets/plugins/iCheck/icheck.min.js');?>" type="text/javascript"></script>
    <script>
      $(function () {
        $('input').iCheck({
          checkboxClass: 'icheckbox_square-blue',
          radioClass: 'iradio_square-blue',
          increaseArea: '20%' // optional
        });
      });
    </script>
</html>