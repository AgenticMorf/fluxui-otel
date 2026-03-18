const otelModuleBase = 'https://esm.sh';
const otelVersion = '1.30.1';

const moduleUrl = (name, version = otelVersion) => `${otelModuleBase}/${name}@${version}?bundle`;

const initializeBrowserTelemetry = async () => {
    if (window.__fluxuiOtelInitialized === true) {
        return;
    }

    window.__fluxuiOtelInitialized = true;

    const [
        traceBase,
        docLoad,
        fetchInstrumentation,
        xhrInstrumentation,
        exporter,
        instrumentation,
        resources,
        traceWeb,
        semanticConventions,
    ] = await Promise.all([
        /* @vite-ignore */ import(moduleUrl('@opentelemetry/sdk-trace-base')),
        /* @vite-ignore */ import(moduleUrl('@opentelemetry/instrumentation-document-load')),
        /* @vite-ignore */ import(moduleUrl('@opentelemetry/instrumentation-fetch')),
        /* @vite-ignore */ import(moduleUrl('@opentelemetry/instrumentation-xml-http-request')),
        /* @vite-ignore */ import(moduleUrl('@opentelemetry/exporter-trace-otlp-http')),
        /* @vite-ignore */ import(moduleUrl('@opentelemetry/instrumentation')),
        /* @vite-ignore */ import(moduleUrl('@opentelemetry/resources')),
        /* @vite-ignore */ import(moduleUrl('@opentelemetry/sdk-trace-web')),
        /* @vite-ignore */ import(moduleUrl('@opentelemetry/semantic-conventions')),
    ]);

    const provider = new traceWeb.WebTracerProvider({
        resource: new resources.Resource({
            [semanticConventions.SemanticResourceAttributes.SERVICE_NAME]: 'app-browser',
        }),
    });

    provider.addSpanProcessor(new traceBase.BatchSpanProcessor(new exporter.OTLPTraceExporter({
        url: '/api/otel/ingest',
    })));

    provider.register();

    instrumentation.registerInstrumentations({
        instrumentations: [
            new docLoad.DocumentLoadInstrumentation(),
            new fetchInstrumentation.FetchInstrumentation(),
            new xhrInstrumentation.XMLHttpRequestInstrumentation(),
        ],
    });

    const tracer = provider.getTracer('app-browser-ui');

    document.addEventListener('click', (event) => {
        const target = event.target;

        if (!(target instanceof HTMLElement)) {
            return;
        }

        const actionElement = target.closest('[data-otel-action]');
        if (!(actionElement instanceof HTMLElement)) {
            return;
        }

        const action = actionElement.dataset.otelAction;
        if (!action) {
            return;
        }

        tracer.startActiveSpan(`ui.click.${action}`, (span) => {
            span.setAttribute('ui.element', actionElement.tagName.toLowerCase());
            span.end();
        });
    });
};

initializeBrowserTelemetry().catch(() => {
    window.__fluxuiOtelInitialized = false;
});