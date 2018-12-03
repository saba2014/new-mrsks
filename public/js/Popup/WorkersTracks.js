class Popup_WorkersTracks extends Popup_extended {
    constructor() {
        super();
    }

    set_popup_text(item) {
        let text = '';
        if (item.properties.name !== undefined) {
            text = '<b>Имя</b>: ' + item.properties.name
        }
        else {
            let date =new Date(item.properties.day*1000);
            let date_text = date.toLocaleString();
            text = '<b>Контролер: </b> ' + item.properties.owner + '<br>' +
                '<b>EMEI:</b>'+item.properties.emei+'<br>'+
                '<b>Дата последней проверки: </b> '+ date_text + '<br>';
                let htmlData=item.properties.HtmlData.split('/');
                htmlData.forEach(function (data) {
                    let tempText=data.split(':');
                    if(data!==''&&data!==undefined)
                    text+='<b>'+tempText[0]+': </b>' + tempText[1]+ '<br>';
                })
        }
        this._popup_text = text;
    }

    get_popup_text() {
        return this._popup_text;
    }
}