function SVG_image(image_width, image_height, color) {
    this.color = color;
    this.image_height = image_height;
    this.image_width = image_width;
}
;

SVG_image.prototype.Get_Image = function (type, unick) {
    this.unick = unick;
    var image = document.createElement('div');
    switch (type) {
        case 'circle':
            image.innerHTML = this._get_circle();
            break;

        case 'triangle':
            image.innerHTML = this._get_triangle();
            break;

        case 'square':
            image.innerHTML = this._get_square();
            break;

        case 'line':
            image.innerHTML = this._get_line();
            break;
        
        case 'opory':
            image.innerHTML = this._get_opory();
            break;
            
        case 'rhombus':
            image.innerHTML = this._get_rhombus();
            break;

        case 'collage':
            image.innerHTML = this._get_collage();
            break;
        case 'worker':
            image.innerHTML = this._get_worker();
            break;
        case 'car':
            image.innerHTML = this._get_car();
            break;
        case 'rhombus_black':
            image.innerHTML = this._get_rhombus_black();
            break;
        default:
            image.setAttribute("class", "col-xs-1");
            image.style.marginRight = "5px";
            return image;
    }
    image.style.width = this.image_width + "px";
    image.style.height = this.image_height + "px";
    image.setAttribute("class", "col-xs-1");
    //image.style.paddingRight = "10px";
    image.style.marginRight = "5px";
    image.style.marginTop = "-1px";
    return image;
};

SVG_image.prototype._get_line = function () {
    return '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"' +
            'viewBox="-282 405.9 30 30" style="enable-background:new -282 405.9 30 30;" xml:space="preserve">' +
            '<style type="text/css">' +
            '.st_' + this.unick + '_0{fill:none;stroke:' + this.color + ';stroke-width:3;stroke-linecap:round;stroke-linejoin:round;}' +
            '.st_' + this.unick + '_1{fill:' + this.color + ';fill-opacity:0.6;stroke:' + this.color + ';stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;}' +
            '</style>' +
            '<path class="st_' + this.unick + '_0" d="M-281,420.9h29"/>' +
            '<path class="st_' + this.unick + '_1" d="M-271,420.9c0,2.2,1.8,4,4,4s4-1.8,4-4c0-2.2-1.8-4-4-4S-271,418.7-271,420.9"/>' +
            '</svg>';
};

SVG_image.prototype._get_opory = function () {
    return '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"' +
            'viewBox="-282 405.9 30 30" style="enable-background:new -282 405.9 30 30;" xml:space="preserve">' +
            '<style type="text/css">' +
            '.st_' + this.unick + '_0{fill:none;stroke:' + this.color + ';stroke-width:3;stroke-linecap:round;stroke-linejoin:round;}' +
            '.st_' + this.unick + '_1{fill:' + this.color + ';fill-opacity:0.6;stroke:' + this.color + ';stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;}' +
            '</style>' +
            '<path class="st_' + this.unick + '_1" d="M-271,420.9c0,2.2,1.8,4,4,4s4-1.8,4-4c0-2.2-1.8-4-4-4S-271,418.7-271,420.9"/>' +
            '</svg>';
};

SVG_image.prototype._get_circle = function () {
    return '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"' +
            'viewBox="-282 408.8 27 27.1" style="enable-background:new -282 408.8 27 27.1;" xml:space="preserve">' +
            '<style type="text/css">' +
            '.ps_' + this.unick + '_0{fill:url(#PS' + this.unick + ');fill-opacity:0.6;stroke:' + this.color + ';stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;' +
            '</style>' +
            '<linearGradient id="PS' + this.unick + '" gradientUnits="userSpaceOnUse" x1="-546.2546" y1="815.8112" x2="-545.2546" y2="814.8112" gradientTransform="matrix(15.7569 0 0 -15.5175 8331.0264 13073.7285)">' +
            '<stop  offset="0" style="stop-color:#FFFFFF"/>' +
            '<stop  offset="0.6" style="stop-color:' + this.color + '"/>' +
            '</linearGradient>' +
            '<path shape-rendering="geometricPrecision" class="ps_' + this.unick + '_0" d="M-263.3,428l-5.1,1.9l-5.1-1.9l-2.7-4.7l1-5.4l4.2-3.5h5.5l4.2,3.5l1,5.4L-263.3,428z"/>' +
            '</svg>';
};

SVG_image.prototype._get_triangle = function () {
    return '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"' +
            'viewBox="-282 405.9 30 30" style="enable-background:new -282 405.9 30 30;" xml:space="preserve">' +
            '<style type="text/css">' +
            '.tp_' + this.unick + '_0{fill:url(#tp_' + this.unick + ');fill-opacity:0.6;stroke:' + this.color + ';stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;' +
            '</style>' +
            '<linearGradient id="tp_' + this.unick + '" gradientUnits="userSpaceOnUse" x1="-543.8571" y1="812.78" x2="-542.8571" y2="811.78" gradientTransform="matrix(14 0 0 -14 7340 11792.8105)">' +
            '<stop  offset="0" style="stop-color:#FFFFFF"/>' +
            '<stop  offset="0.6" style="stop-color:' + this.color + '"/>' +
            '</linearGradient>' +
            '<path shape-rendering="geometricPrecision" class="tp_' + this.unick + '_0" d="M-274,427.9l7-14l7,14H-274z"/>' +
            '</svg>';
};

SVG_image.prototype._get_square = function () {
    return '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"' +
            'viewBox="-282 405.9 30 30" style="enable-background:new -282 405.9 30 30;" xml:space="preserve">' +
            '<style type="text/css">' +
            '.rp_' + this.unick + '_0{fill:url(#rp_' + this.unick + ');fill-opacity:0.6;stroke:' + this.color + ';stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;' +
            '</style>' +
            '<linearGradient id="rp_' + this.unick + '" gradientUnits="userSpaceOnUse" x1="-543.8571" y1="812.78" x2="-542.8571" y2="811.78" gradientTransform="matrix(14 0 0 -14 7340 11792.8105)">' +
            '<stop  offset="0" style="stop-color:#FFFFFF"/>' +
            '<stop  offset="0.6" style="stop-color:' + this.color + '"/>' +
            '</linearGradient>' +
            '<path shape-rendering="geometricPrecision" class="rp_' + this.unick + '_0" d="M-274,427.9v-14h14v14H-274z"/>' +
            '</svg>';
};

SVG_image.prototype._get_rhombus = function () {
    return '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"' +
            'viewBox="-282 408.9 26.8 26.9" style="enable-background:new -282 408.9 26.8 26.9;" xml:space="preserve">' +
            '<style type="text/css">' +
            '.ztp_' + this.unick + '_0{fill:url(#ZTP' + this.unick + ');fill-opacity:0.6;stroke:' + this.color + ';stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;' +
            '</style>' +
            '<linearGradient id="ZTP' + this.unick + '" gradientUnits="userSpaceOnUse" x1="-546.3489" y1="816.3859" x2="-545.3489" y2="815.3859" gradientTransform="matrix(16 0 0 -16 8465 13476.3701)">' +
            '<stop  offset="0" style="stop-color:#FFFFFF"/>' +
            '<stop  offset="0.6" style="stop-color:' + this.color + '"/>' +
            '</linearGradient>' +
            '<path shape-rendering="geometricPrecision" class="ztp_' + this.unick + '_0" d="M-260.6,422.2l-8,8l-8-8l8-8L-260.6,422.2z"/>' +
            '</svg>';
};

SVG_image.prototype._get_rhombus_black = function(){
    return '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"' +
        'viewBox="-282 408.9 26.8 26.9" style="enable-background:new -282 408.9 26.8 26.9;" xml:space="preserve">' +
        '<style type="text/css">' +
        '.ztp_' + this.unick + '1' + '_0{fill:' + this.color + ';fill-opacity:0.6;stroke:' + '#000000' + ';stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;' +
        '</style>' +
        '<linearGradient id="ZTP' + this.unick + '" gradientUnits="userSpaceOnUse" x1="-546.3489" y1="816.3859" x2="-545.3489" y2="815.3859" gradientTransform="matrix(16 0 0 -16 8465 13476.3701)">' +
        '<stop  offset="0" style="stop-color:#FFFFFF"/>' +
        '<stop  offset="0.6" style="stop-color:' + this.color + '"/>' +
        '</linearGradient>' +
        '<path shape-rendering="geometricPrecision" class="ztp_' + this.unick + '1' + '_0" d="M-260.6,422.2l-8,8l-8-8l8-8L-260.6,422.2z"/>' +
        '</svg>';
}

SVG_image.prototype._get_collage = function () {
    return '<svg version="1.1" id="Слой_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"' +
            'viewBox="-282 408.9 26.8 27" style="enable-background:new -282 408.9 26.8 27;" xml:space="preserve">' +
            '<style type="text/css">' +
            '.st0{fill:url(#SVGID_1_);fill-opacity:0.6;stroke:#959595;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;}' +
            '.st1{fill:url(#SVGID_2_);fill-opacity:0.6;stroke:#8E008E;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;}' +
            '.st2{fill:url(#SVGID_3_);fill-opacity:0.6;stroke:#F60000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;}' +
            '</style>' +
            '<linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="-546.2653" y1="815.8505" x2="-545.2653" y2="814.8505" gradientTransform="matrix(15.7569 0 0 -15.5175 8331.0264 13073.7285)">' +
            '<stop  offset="0" style="stop-color:#FFFFFF"/>' +
            '<stop  offset="0.6" style="stop-color:#959595"/>' +
            '</linearGradient>' +
            '<path shape-rendering="geometricPrecision" class="st0" d="M-263.4,427.4l-5.1,1.9l-5.1-1.9l-2.7-4.7l1-5.4l4.2-3.5h5.5l4.2,3.5l1,5.4L-263.4,427.4z"/>' +
            '<linearGradient id="SVGID_2_" gradientUnits="userSpaceOnUse" x1="-544.3912" y1="812.3103" x2="-543.5233" y2="811.4423" gradientTransform="matrix(14 0 0 -14 7340 11792.8105)">' +
            '<stop  offset="0" style="stop-color:#FFFFFF"/>' +
            '<stop  offset="0.6" style="stop-color:#8E008E"/>' +
            '</linearGradient>' +
            '<path shape-rendering="geometricPrecision" class="st1" d="M-280,431.1l6.1-12.2l6.1,12.2H-280z"/>' +
            '<linearGradient id="SVGID_3_" gradientUnits="userSpaceOnUse" x1="-543.3931" y1="812.3396" x2="-542.6057" y2="811.5522" gradientTransform="matrix(14 0 0 -14 7340 11792.8105)">' +
            '<stop  offset="0" style="stop-color:#FFFFFF"/>' +
            '<stop  offset="0.6" style="stop-color:#F60000"/>' +
            '</linearGradient>' +
            '<path shape-rendering="geometricPrecision" class="st2" d="M-267.5,431l0-11h11.1l0,11L-267.5,431z"/>' +
            '</svg>';
};

SVG_image.prototype._get_worker = function () {
    return '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 311.954 311.954"' +  
            'xmlns:xlink="http://www.w3.org/1999/xlink" enable-background="new 0 0 311.954 311.954">' +
        '<g>' +
          '<g>' +
            '<path d="m143.621,280.135v17.766c0,7.761 6.292,14.053 14.053,14.053 7.761,0 14.053-6.292 14.053-14.053v-14.672h-17.667c-3.848,0-7.427-1.145-10.439-3.094z"/>' +
          '</g>' +
          '<g>' +
            '<path d="m140.585,65.604c10.484,0 19.414-6.647 22.809-15.956h-45.618c3.394,9.309 12.325,15.956 22.809,15.956z"/>' +
          '</g>' +
          '<g>' +
            '<g>' +
              '<path d="m222.976,203.851h-10.57c0-4.238 0.193-7.057-1.166-11.243-1.868-5.751-5.862-10.549-11.051-13.469l-.393-78.225c-0.076-15.205-12.508-27.911-27.714-27.911h-2.087l-21.597,42.467v-29.522l4.93-8.611c1.105-1.929-0.288-4.332-2.512-4.332h-20.471c-2.19,0-3.587,2.338-2.548,4.266l4.673,8.677v29.524l-21.274-42.467h-2.11c-15.206,0-27.638,12.706-27.714,27.911l-.433,86.037c-0.032,6.468 5.184,11.737 11.653,11.77 0.02,0 0.039,0 0.06,0 6.44,0 11.677-5.204 11.71-11.653l.433-86.037c0.007-1.361 1.182-2.442 2.57-2.299 1.206,0.124 2.077,1.23 2.077,2.443l.005,196.725c0,7.987 6.663,14.417 14.733,14.037 7.559-0.356 13.374-6.867 13.374-14.434v-23.643c-1.749-2.901-2.775-6.286-2.775-9.913v-52.059c0-3.628 1.026-7.012 2.775-9.913v-13.2c0-1.643 1.23-3.115 2.871-3.201 1.75-0.092 3.197,1.3 3.197,3.03v7.098c3.012-1.949 6.591-3.094 10.439-3.094h11.736c-1.361,4.19-1.166,7.026-1.166,11.243h-10.57c-4.44,0-8.038,3.599-8.038,8.038v52.058c0,4.44 3.599,8.039 8.039,8.039h68.916c4.44,0 8.039-3.599 8.039-8.039v-52.059c-0.003-4.44-3.601-8.039-8.041-8.039zm-51.462-20.638c-0.015-0.09-0.024-0.182-0.041-0.271v-81.897c0-1.351 1.093-2.447 2.444-2.451 1.351-0.004 2.45,1.087 2.457,2.438l.392,78.153c-1.933,1.098-3.701,2.455-5.252,4.028zm24.965,20.17h-15.923c0-3.513-0.199-4.947 0.889-7.052 1.967,1.495 4.412,2.391 7.071,2.391 0.019,0 0.04,0 0.059,0 2.639-0.013 5.062-0.909 7.013-2.393 1.092,2.109 0.891,3.548 0.891,7.054z"/>' +
            '</g>' +
          '</g>' +
          '<g>' +
            '<path d="m116.313,38.873h47.854c4.398,0 7.964-3.565 7.964-7.964 0-2.773-1.42-5.213-3.57-6.639-0.536-4.432-2.097-8.546-4.445-12.099-4.118-6.232-10.644-10.729-18.254-12.171v13.594c0,3.104-2.517,5.621-5.621,5.621-3.104,0-5.621-2.517-5.621-5.621v-13.456c-7.315,1.577-13.572,5.99-17.566,12.034-2.271,3.436-3.806,7.398-4.39,11.665-2.56,1.324-4.314,3.991-4.314,7.073-0.001,4.398 3.565,7.963 7.963,7.963z"/>' +
          '</g>' +
        '</g>' +
      '</svg>';
};

SVG_image.prototype._get_car = function () {
    return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" ' +
        'x="0px" y="0px" viewBox="0 0 521.886 521.886" style="enable-background:new 0 0 521.886 521.886;"' + 
        'xml:space="preserve">' +
        '<g>' +
	'<path d="M494.03,228.989l-68.72-23.735l-39.809-67.593c-13.327-21.001-22.402-27.855-62.358-27.855H27.866' +
		'C12.479,109.807,0,122.286,0,137.662v148.169v26.527v14.6c0,15.381,12.479,27.865,27.866,27.865h14.788' +
		'c1.836-30.591,27.152-54.854,58.201-54.854c31.048,0,56.363,24.263,58.202,54.854h200.24c1.106-18.06,10.519-35.368,26.973-45.781' +
		'c27.237-17.258,63.3-9.176,80.557,18.058c5.473,8.646,8.383,18.179,8.952,27.724h18.255c15.387,0,27.853-12.484,27.853-27.865' +
		'v-70.103C521.886,241.463,510.474,234.41,494.03,228.989z M389.464,212.847H262.479c-1.908,0-3.438-1.529-3.438-3.423v-67.431' +
		'c0-1.894,1.529-3.423,3.438-3.423h90.94c1.882,0,4.687,0.7,6.074,3.423l33.39,67.426' +
		'C392.883,211.317,391.357,212.847,389.464,212.847z M516.983,298.952c0,5.145-4.169,9.315-9.313,9.315h-28.717' +
		'c-5.144,0-9.313-4.171-9.313-9.315v-20.958c0-5.144,4.17-9.312,9.313-9.312h28.717c5.145,0,9.313,4.169,9.313,9.312V298.952z' +
		' M100.155,306.174c-29.2,0-52.957,23.762-52.957,52.958c0,29.2,23.756,52.947,52.957,52.947c29.198,0,52.946-23.747,52.946-52.947' +
		'C153.101,329.936,129.354,306.174,100.155,306.174z M100.155,395.276c-19.944,0-36.146-16.215-36.146-36.138' +
		'c0-19.924,16.211-36.141,36.146-36.141c19.931,0,36.137,16.217,36.137,36.141C136.292,379.061,120.086,395.276,100.155,395.276z' +
		' M417.745,306.174c-29.2,0-52.96,23.762-52.96,52.958c0,29.2,23.76,52.947,52.96,52.947c29.197,0,52.953-23.747,52.953-52.947' +
		'C470.698,329.936,446.942,306.174,417.745,306.174z M417.745,395.276c-19.937,0-36.147-16.215-36.147-36.138' +
		'c0-19.924,16.217-36.141,36.147-36.141c19.938,0,36.141,16.217,36.141,36.141C453.885,379.061,437.678,395.276,417.745,395.276z"/>' +
        '</g>' +
        '</svg>';
};

SVG_image.prototype.get_svg_by_url=function(url){
    let img = document.createElement('div');
    img.style.marginRight = '0px';
    img.style.marginTop = '-1px';
    img.style.width = "20px";
    img.style.height = "20px";
    img.innerHTML = "<img src='"+url+"' width='20px' height='20px' style='margin-left: -5px; margin-top: -5px'>";
    return img;
};