const fs = require('fs');
const path = require('path');

const rootDir = process.cwd();

// Directories/files to skip
const ignoreList = ['node_modules', '.git', 'build', 'publish-gate.zip', 'mirm-editorial-guard.zip', 'rename.js'];

// Replacements (order matters to avoid partial replaces!)
const replacements = [
	{ search: /Publish_Gate/g, replace: 'MirM_Editorial_Guard' },
	{ search: /PUBLISH_GATE/g, replace: 'MIRM_EDITORIAL_GUARD' },
	{ search: /publishGate/g, replace: 'mirmEditorialGuard' },
	{ search: /publish_gate/g, replace: 'mirm_editorial_guard' },
	{ search: /publish-gate/g, replace: 'mirm-editorial-guard' },
	{ search: /Publish Gate/g, replace: 'MirM Editorial Guard' },
];

function processDirectory(dir) {
	const files = fs.readdirSync(dir);

	for (const file of files) {
		if (ignoreList.includes(file)) continue;

		const fullPath = path.join(dir, file);
		const stat = fs.statSync(fullPath);

		if (stat.isDirectory()) {
			processDirectory(fullPath);
		} else {
			processFile(fullPath);
		}
	}
}

function processFile(filePath) {
	if (filePath.endsWith('.png') || filePath.endsWith('.zip') || filePath.endsWith('.jpg') || filePath.endsWith('.webp')) return;

	let content = fs.readFileSync(filePath, 'utf8');
	let original = content;

	for (const r of replacements) {
		content = content.replace(r.search, r.replace);
	}

	if (content !== original) {
		fs.writeFileSync(filePath, content, 'utf8');
		console.log(`Updated content: ${filePath}`);
	}
}

function renameFiles(dir) {
	const files = fs.readdirSync(dir);
	
	for (const file of files) {
		if (ignoreList.includes(file)) continue;
		
		const fullPath = path.join(dir, file);
		const stat = fs.statSync(fullPath);
		
		if (stat.isDirectory()) {
			renameFiles(fullPath);
		}
		
		if (file.includes('publish-gate')) {
			const newName = file.replace('publish-gate', 'mirm-editorial-guard');
			const newPath = path.join(dir, newName);
			fs.renameSync(fullPath, newPath);
			console.log(`Renamed: ${file} -> ${newName}`);
		}
	}
}

console.log('Starting content replacements...');
processDirectory(rootDir);

console.log('Starting file renames...');
renameFiles(rootDir);

console.log('Done!');
