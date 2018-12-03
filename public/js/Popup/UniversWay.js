class Popup_univers_way extends  Popup_electric{
    constructor(){
        super();
    }

    set popup_text(item){
        this._popup_text = "";
        let type = item.properties.TypeByTplnr;
        if (type==="Подстанции"||type==="ТП"||type==="РП"){
            let popup = new Popup_ps();
            popup.popup_text = item;
            this._popup_text = popup.popup_text;
        }
        if (type==="ЛЭП"){
            let popup = new Popup_line();
            popup.popup_text = item;
            this._popup_text = popup.popup_text;
        }
        if (type==="Опора"||type==="Отпайка"){
            let popup = new Popup_opory();
            popup.popup_text = item;
            this._popup_text = popup.popup_text;
        }
    }

    get popup_text(){
        return this._popup_text;
    }
}