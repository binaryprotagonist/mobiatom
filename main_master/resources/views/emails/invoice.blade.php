<!DOCTYPE html
   PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
   <title>Choose a new password for Canvas</title>
   <style type="text/css" rel="stylesheet" media="all">
      *:not(br):not(tr):not(html) {
         font-family: Roboto, sans-serif;
         -webkit-box-sizing: border-box;
         box-sizing: border-box;
      }

      body {
         width: 100% !important;
         height: 100%;
         margin: 0;
         line-height: 1.4;
         background-color: #f7f5f6;
         color: #839197;
         -webkit-text-size-adjust: none;
      }

      a {
         color: #414EF9;
      }

      .email-wrapper {
         width: 100%;
         margin: 0;
         padding: 0;
         background-color: #F5F7F9;
      }

      .email-content {
         max-width: 600px;
         margin: 0;
         padding: 0;
      }

      .email-masthead {
         padding-top: 25px;
         text-align: center;
      }

      .email-masthead_logo {
         max-width: 400px;
         border: 0;
      }

      .email-masthead_name {
         font-size: 16px;
         font-weight: bold;
         color: #839197;
         text-decoration: none;
         text-shadow: 0 1px 0 white;
      }

      .email-body {
         width: 100%;
         margin: 0;
         padding: 0;
         border-top: 1px solid #E7EAEC;
         border-bottom: 1px solid #E7EAEC;
         background-color: #FFFFFF;
      }

      .email-body_inner {
         width: 570px;
         margin: 0 auto;
         padding: 0;
      }

      .email-footer {
         width: 570px;
         margin: 0 auto;
         padding: 0;
         text-align: center;
      }

      .email-footer p {
         color: #839197;
      }

      .body-action {
         width: 100%;
         margin: 30px auto;
         padding: 0;
         text-align: center;
      }

      .body-sub {
         margin-top: 25px;
         padding-top: 25px;
         border-top: 1px solid #E7EAEC;
      }

      .content-cell {
         padding: 35px;
      }

      .align-right {
         text-align: right;
      }

      h1 {
         margin-top: 0;
         color: #101234;
         font-size: 19px;
         font-weight: bold;
         text-align: left;
      }

      h2 {
         margin-top: 0;
         color: #101234;
         font-size: 16px;
         font-weight: bold;
         text-align: left;
      }

      h3 {
         margin-top: 0;
         color: #101234;
         font-size: 14px;
         font-weight: bold;
         text-align: left;
      }

      p {
         margin-top: 0;
         color: #839197;
         font-size: 16px;
         line-height: 1.5em;
         text-align: left;
      }

      p.sub {
         font-size: 12px;
      }

      p.center {
         text-align: center;
      }

      .btn {
         display: inline-block;
         font-weight: 400;
         color: #212529;
         text-align: center;
         vertical-align: middle;
         -webkit-user-select: none;
         -moz-user-select: none;
         -ms-user-select: none;
         user-select: none;
         background-color: transparent;
         border: 1px solid transparent;
         padding: .375rem .75rem;
         font-size: 1rem;
         line-height: 1.5;
         border-radius: .25rem;
         transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
      }

      .btn-outline-primary {
         color: #007bff;
         border-color: #007bff;
      }

      .btn-outline-primary:hover {
         color: #fff;
         background-color: #007bff;
         border-color: #007bff;
      }

      .button-main {
         background-color: #64d7b1;
      }

      /*Media Queries ------------------------------ */
      @media only screen and (max-width: 600px) {

         .email-body_inner,
         .email-footer {
            width: 100% !important;
         }
      }

      @media only screen and (max-width: 500px) {
         .button {
            width: 100% !important;
         }
      }
   </style>
</head>

<body>
   <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0">
      <tr>
         <td align="center">
            <table class="email-content" width="100%" cellpadding="0" cellspacing="0">
               <tr>
                  <td class="email-masthead">
                     <div
                        style="background-color:#101234;border-top-left-radius:2px;border-top-right-radius:2px;text-align: left;padding: 30px;">
                        <a href="#" class="email-masthead_name">
                           <img src="{{ $logo }}" width="100" height="100" alt="LOGO" />
                        </a>
                     </div>
                  </td>
               </tr>
               <tr>
                  <td class="email-body" width="100%">
                     <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0">
                        <tr>
                           <td class="content-cell" align="center">
                              {{ $content }}
                           </td>
                        </tr>
                        <tr>
                           <td class="content-cell" align="center">
                              <a href="{{ $url }}" target="_blank" class="btn btn-success">View {{ $tag }}</a>
                           </td>
                        </tr>
                     </table>
                  </td>
               </tr>
               <tr>
                  <td>
                     <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0">
                        <tr>
                           <td class="content-cell">
                              <p class="sub center">Powered by <a href="#" class="email-masthead_name">
                                    <img src="https://mobiato-msfa.com/assets/images/logo.png" alt="LOGO"
                                       style="width:150px;" />
                                 </a></p>
                           </td>
                        </tr>
                     </table>
                  </td>
               </tr>
            </table>
         </td>
      </tr>
   </table>
</body>

</html>