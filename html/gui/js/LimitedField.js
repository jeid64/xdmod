XDMoD.LimitedField = Ext.extend(Ext.form.TextField, {

		characterLimit: 0,
		enableKeyEvents: true,
		vpattern: '',
		
		listeners: {
			'blur': function () { this.setValue(this.getValue().substring(0, Math.min(this.characterLimit, this.getValue().length))); },
			'keydown': function (a,e) {
				if (
					this.getValue().length == this.characterLimit && 
					e.getCharCode() != 8 &&
					e.getCharCode() != 37 &&
					e.getCharCode() != 38 &&
					e.getCharCode() != 39 &&
					e.getCharCode() != 40
				) e.stopEvent(); 
			}
		
		},
		
		validate: function () {
			
  			var re = new RegExp(this.vpattern);
  			
  			return (this.getValue().match(re)) ? true : false;

		}
		
});

Ext.reg('xd-inputfield', XDMoD.LimitedField);