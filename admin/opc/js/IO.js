class IO
{
    init()
    {
        return Promise.all([
            new Promise((res, rej) => ioCall('opcGetIOFunctionNames', [], res, rej))
                .then(names => this.generateIoFunctions(names)),
            new Promise((res, rej) => ioCall('opcGetPageIOFunctionNames', [], res, rej))
                .then(names => this.generateIoFunctions(names)),
        ]);
    }

    generateIoFunctions(names)
    {
        names.forEach(name => {
            this[name] = this.generateIoFunction('opc' + capitalize(name));
        });
    }

    generateIoFunction(publicName)
    {
        return function(...args) {
            let jqxhr   = null;
            opc.emit('io.' + publicName, args);
            let promise = new Promise((res, rej) => {
                jqxhr = ioCall(
                    publicName,
                    args,
                    (...resolveArgs) => {
                        opc.emit('io.' + publicName + ':resolve', resolveArgs);
                        return res.apply(this, resolveArgs);
                    },
                    (...rejectArgs) => {
                        opc.emit('io.' + publicName + ':reject', rejectArgs);
                        return rej.apply(this, rejectArgs);
                    }
                );
            });
            promise.jqxhr = jqxhr;
            return promise;
        };
    }

    createPortlet(portletClass)
    {
        return this.getPortletPreviewHtml({class: portletClass});
    }
}
