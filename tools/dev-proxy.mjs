/**
 * Dev-only reverse proxy so browser tooling (Claude preview panel) can
 * observe the wp-env WordPress site. Forwards :4180 → 127.0.0.1:8888 and
 * rewrites textual responses so every asset URL also flows through :4180
 * (the preview browser cannot reach :8888 directly).
 */

import http from "node:http";

const UPSTREAM = { host: "127.0.0.1", port: 8888 };
const PORT = 4180;
const FROM = new RegExp(`localhost:${UPSTREAM.port}`, "g");
const TO = `localhost:${PORT}`;
const TEXTUAL = /^(text\/|application\/(javascript|json|xml))/;

http
  .createServer((req, res) => {
    const proxied = http.request(
      {
        ...UPSTREAM,
        path: req.url,
        method: req.method,
        headers: {
          ...req.headers,
          host: `localhost:${UPSTREAM.port}`,
          "accept-encoding": "identity",
        },
      },
      (upstream) => {
        const headers = { ...upstream.headers };
        if (typeof headers.location === "string") {
          headers.location = headers.location.replace(FROM, TO);
        }

        const type = String(headers["content-type"] ?? "");
        if (TEXTUAL.test(type)) {
          const chunks = [];
          upstream.on("data", (c) => chunks.push(c));
          upstream.on("end", () => {
            const body = Buffer.concat(chunks).toString("utf8").replace(FROM, TO);
            delete headers["content-length"];
            delete headers["transfer-encoding"];
            res.writeHead(upstream.statusCode ?? 502, headers);
            res.end(body);
          });
          return;
        }

        res.writeHead(upstream.statusCode ?? 502, headers);
        upstream.pipe(res);
      }
    );
    proxied.on("error", () => {
      res.writeHead(502);
      res.end("wp-env is not running");
    });
    req.pipe(proxied);
  })
  .listen(PORT, () => console.log(`proxy ready on http://localhost:${PORT}`));
