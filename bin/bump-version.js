var fs = require('fs');
var semver = require('semver');

let versionFile = './power-of-families/version.txt';
fs.readFile(versionFile, 'utf8', (err, data) => {
  if (err) throw err;
  const currentVersion = semver.clean(data);
  const newVer = semver.inc(currentVersion, 'patch');
  console.log(newVer);
  fs.writeFile(versionFile,newVer, 'utf8');
});