/**
 * Post-start tuning for the wp-env WordPress container.
 *
 * The default image caps opcache at 4000 files; WordPress + WooCommerce is
 * ~8000+, so on a Windows bind mount every request recompiles half the
 * codebase (~19s/page). Raising the caps brings warm pages to ~5s.
 * Runs automatically via `npm run env:start`; safe to re-run.
 */

import { execSync } from "node:child_process";

const INI = [
  "opcache.max_accelerated_files=20000",
  "opcache.memory_consumption=256",
  "opcache.interned_strings_buffer=16",
  "opcache.revalidate_freq=60",
  "realpath_cache_size=4096K",
  "realpath_cache_ttl=120",
].join("\\n");

const container = execSync(
  'docker ps --filter "name=wordpress" --format "{{.Names}}"',
  { encoding: "utf8" }
)
  .split("\n")
  .find((name) => name && !name.includes("tests-wordpress"));

if (!container) {
  console.error("wp-env-tune: no running wordpress container found — skipped.");
  process.exit(0);
}

execSync(
  `docker exec ${container} sh -c "printf '${INI}\\n' > /usr/local/etc/php/conf.d/zz-skirttique-dev.ini && apachectl -k graceful"`,
  { stdio: "inherit" }
);
console.log(`wp-env-tune: opcache tuning applied to ${container}.`);
