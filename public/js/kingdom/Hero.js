class Hero {
    constructor(info) {
        this.name = info.name;
        this.rights = [];
        this.style = "";
        this.active = false;
        this.img = info.iconPath;
        if ((this.img === undefined) || (this.img === "")) {
            this.img = "/img/icons/worker.svg";
        }
        this.amount = 0;
        if (info.count){
            if (info.count===-1)
                this.amount='&#8734;';
            else this.amount=info.count;
        }
        this.inUse = 0;
        this.parent = info.parent;
        this.feature = info.feature;
        if (info.attributes) {
            for (let i = 0; i < info.attributes.length; i++) {
                if (info.attributes[i].count > this.amount) this.amount = info.attributes[i].count;
                info.attributes[i]['parent'] = this;
                let obj = new Right(info.attributes[i]);
                this.rights.push(obj);
            }
        }
        this.popup = new Personal();
    }

    decreaseAmount() {
        //let isDec = 0;
        if (this.amount === '&#8734;') {
            return true;
        }
        if (this.amount > 0) {
            this.amount--;
            this.inUse++;
            for (let i = 0; i < this.rights.length; i++) {
                this.rights[i].decreaseAmount();
                if (this.amount === 0) this.rights[i].dis = true;
            }
            return true;
        }
        else return false;
    }

    returnAmount() {
        if (this.amount === '&#8734;') {
            return true;
        }
        this.amount += this.inUse;
        this.inUse = 0;
        for (let i = 0; i < this.rights.length; i++)
            this.rights[i].returnAmount();
    }

    returnOne() {
        if (this.amount === '&#8734;') {
            return true;
        }
        this.amount++;
        this.inUse--;
        this.parent.returnOne();
        for (let i = 0; i < this.rights.length; i++)
            this.rights[i].returnOne();
    };

    /*test() {
        for (let i = 0; i < this.rights.length; i++)
            this.rights.decreaseAmount();
        if (this.amount !== 0) this.amount--;
    }*/

}