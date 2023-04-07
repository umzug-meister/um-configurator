const { src, dest } = require("gulp");

const zip = require("gulp-zip");

const PLUGIN_SRC = "src/**/*";

const PLUGIN_ARCHIVE_NAME = `um-configurator.zip`;

async function zipPlugin() {
  return src(PLUGIN_SRC).pipe(zip(PLUGIN_ARCHIVE_NAME)).pipe(dest("dist"));
}

exports.zip = zipPlugin;
