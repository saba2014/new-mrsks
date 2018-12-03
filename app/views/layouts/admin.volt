<script>
    // временный коммент, удалить отсюда эти строки?
// var adminenabled = true;
// var myRole = {{ role }};
</script>
{{ assets.outputJs("admin_js") }}


<body>

    <span id="loader" us-spinner="{radius:30, width:8, length: 16}"></span>
    {{ content()}}  
    <div id="copyright">&nbsp;2018&nbsp;&copy;&nbsp;ООО&nbsp;РЦ&nbsp;&copy;&nbsp;<a href="http://navikey.org/" target="Navikey">Navikey</a>&trade;</div>
</body>

