<!DOCTYPE html>
<html>
   <head>
      <title>Please Wait......</title>    
   </head>
   <body>


<h2 class="center-align grey-text darken-3">Please do not refresh this page...
</h2>


<form method="post" action="{{ env('PAYTM_TXN_URL') }}" name="f1">

           
         @foreach($paramList as $name => $value)
          <input type="hidden" name="{{ $name }}" value="{{$value }}">  
         @endforeach         
         
         <input type="hidden" name="CHECKSUMHASH" value="{{ $checkSum }}">
         {{ csrf_field() }}
       
      <script type="text/javascript">
         document.f1.submit();
      </script>
   
</form>

</body>
</html>
