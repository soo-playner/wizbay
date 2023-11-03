<!DOCTYPE html>
<html lang="">
<head>
<script>
window.front_env = {!! frontEnvJson() !!};
</script>
<meta charset=utf-8>
<link rel="icon" type="image/png" sizes="96x96" href="/favicon.ico">
<meta name=viewport content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0">
<meta name=author content="{{ envDB('APP_NAME') }}">
<meta property=og:site_name content="{{ envDB('APP_NAME') }}">
<meta name=keywords content="{{ envDB('APP_KEYWORD') }}">
<meta property=og:title content="{{ $title }}">
<meta name=twitter:title content="{{ $title }}">
<meta name=description content="{{ $description }}">
<meta property=og:description content="{{ $description }}">
<meta name=twitter:description content="{{ $description }}">
<meta property=og:type content=website>
<title>{{ $title }}</title>
<link rel=stylesheet href=//pro.fontawesome.com/releases/v5.10.0/css/all.css>
<link rel=stylesheet href=/assets/css/bootstrap.min.css>
<link rel=stylesheet href=/assets/css/default.css>
<link href=/css/app.d5c0cce0.css rel=preload as=style>
<link href=/css/chunk-vendors.b2367c11.css rel=preload as=style>
<link href=/js/app.19954cf8.js rel=preload as=script>
<link href=/js/chunk-vendors.3f2bd4ce.js rel=preload as=script>
<link href=/css/chunk-vendors.b2367c11.css rel=stylesheet>
<link href=/css/app.d5c0cce0.css rel=stylesheet>
{!!  analytics() !!}
</head>
<body>
<div id="hd_pop">
    <!-- <h2>팝업레이어 알림</h2> -->

<style>

	.hd_pops{position:absolute;z-index:10000;background:white;}
	.hd_pops_footer{background:black;text-align:right;}
	.hd_pops_footer button{margin:3px;padding:3px 10px;line-height:20px;height:inherit;font-size:13px;box-shadow:none;letter-spacing:0;border-radius:0;background:transparent;border:1px solid #222;color:#f5f5f5;}
	.hd_pops_footer button:hover{background:rgba(200,200,200,0.1);color:white;}
	.hd_pops_con, .hd_pops_con img{max-width:100%;}

	@media (max-width: 768px){
		.hd_pops{top:100px !important; left:2px !important;}
        .hd_pops_con{max-width:100%;} 
        .hd_pops_con img{max-width:70%;}
		.hd_pops_con, .hd_pops_con img{width:100% !important;}
		.hd_pops_con{height:auto !important;}
	}
</style>
    <?php 
    $pop_key = isset($_COOKIE['hd_pops_9']);

    if(!$pop_key){?>
       <!--  <div id="hd_pops_9" class="hd_pops" style="top:100px;left:1200px;border: 2px solid black">
            <div class="hd_pops_con" style="width:300px;height:460px">
                <p>
                <p style="text-align: center;" align="center">
                <img src="/assets/auth/arty_logo.png" title="arty_logo" width="200px"></p>
                
                    <p style="text-align: center;" align="center">안녕하세요? WIZBAY 입니다&nbsp;</p>
                    <p style="text-align: center;" align="center">2023년 7월. 청년문화예술 ARTY가 WIZBAY에 상장됩니다.</p>
                    <p style="text-align: center;" align="center">-</p>
                    <p style="text-align: center;" align="center">ARTY는 문화예술청년들의 가치를 보호하며&nbsp;</p>
                    <p style="text-align: center;" align="center">다양하고 지속가능한 콘텐츠 제작을 지원하는 생태계 입니다.&nbsp;</p>
                    <p style="text-align: center;" align="center">국내 유일한 실물자산 기반 청년 문화예술 ARTY Project를 통해</p>
                    <p style="text-align: center;" align="center">안전하고 높은 가치에 투자하세요.&nbsp;</p>
                    <p style="text-align: center; " align="center">WIZBAY</p>
                    <p style="text-align: center; "><br></p>
                
                <div style="text-align:center">
                
                <p style="font-size:15px;"> 7월 26일(수) </p>
                <p style="font-size:28px;">ARTY </p>
                <p style="font-size:20px;"> [ 상장 예정 ]</p>
                <br>
                <p> WIZBAY</p>
                </div>

                </p>
            </div>
            <div class="hd_pops_footer">
                <button class="hd_pops_reject hd_pops_9 24"><strong>24</strong>시간 동안 다시 열람하지 않습니다.</button>
                <button class="hd_pops_close hd_pops_9">닫기</button>
            </div>
        </div> -->
    <?php }?>
</div>


<noscript><strong>We're sorry but Webpack App doesn't work properly without JavaScript enabled. Please enable it to continue.</strong></noscript>
<div id=app></div>
<script src=/js/chunk-vendors.3f2bd4ce.js></script>
<script src=/js/app.19954cf8.js></script>
<script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>

<script>
$(function() {
$(".hd_pops_reject").click(function() {
    var id = $(this).attr('class').split(' ');
    var ck_name = id[1];
    var exp_time = parseInt(id[2]);
    $("#"+id[1]).css("display", "none");
    set_cookie(ck_name, 1, exp_time, "https://wizbay.org/");
});

$('.hd_pops_close').click(function() {
    console.log("close");
    var idb = $(this).attr('class').split(' ');
    $('#'+idb[1]).css('display','none');
});
$("#hd").css("z-index", 1000);
});

// 쿠키 입력
function set_cookie(name, value, expirehours, domain) 
{
    var today = new Date();
    today.setTime(today.getTime() + (60*60*1000*expirehours));
    document.cookie = name + "=" + escape( value ) + "; path=/; expires=" + today.toGMTString() + ";";
    if (domain) {
        document.cookie += "domain=" + domain + ";";
    }
}

// 쿠키 얻음
function get_cookie(name) 
{
    var find_sw = false;
    var start, end;
    var i = 0;

    for (i=0; i<= document.cookie.length; i++)
    {
        start = i;
        end = start + name.length;

        if(document.cookie.substring(start, end) == name) 
        {
            find_sw = true
            break
        }
    }

    if (find_sw == true) 
    {
        start = end + 1;
        end = document.cookie.indexOf(";", start);

        if(end < start)
            end = document.cookie.length;

        return document.cookie.substring(start, end);
    }
    return "";
}
</script>
</body>
</html>
