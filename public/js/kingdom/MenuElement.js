class MenuElement {
    constructor(name, count, img = undefined, childArr = undefined, href = "") {
        this.img = img;
        this.amount = 0;
        if (count){
            if (count===-1)
                this.amount='&#8734;';
            else this.amount=count;
        }
        this.inUse = 0;
        this.style = "";
        this.active = false;
        this.name = name;
        this.squads = [];
        if (childArr !== undefined) {
            for (let i=0;i<childArr.length;i++){
                childArr[i]['parent']=this;
                let obj = new Squad(childArr[i]);
                this.squads.push(obj);
            }
        }
        this.href = href;
    }

    decreaseAmount() {
        if (this.amount === '&#8734;') {
            return true;
        }
        if (this.amount !== 0) {
            this.amount--;
            this.inUse++;
        }
    }

    returnAmount(){
        if (this.amount === '&#8734;') {
            return true;
        }
        this.amount+=this.inUse;
        this.inUse = 0;
        for (let i=0;i<this.squads.length;i++)
            this.squads[i].returnAmount();
    }

    returnOne(){
        if (this.amount === '&#8734;') {
            return true;
        }
        this.amount++;
        this.inUse--;
    }

    open() {
        this.style = "active";
    }

    close() {
        this.style = "";
    }
}