/**
 * Build sequence task
 *
 * @since 4.1.3
 * @version 1.0.0
 */
const {spawn} = require('child_process');

console.log('Starting build...');

// Run command: npm run start
const buildJS = spawn('npm', ['run', 'start'], {
	stdio: 'inherit',
	shell: true,
});

buildJS.on('exit', () => {
	console.log('Build finished.');
});

buildJS.on('spawn', () => {
	// Run command: npm run build-makepot-zip
	const releaseProcess = spawn('npm', ['run', 'build-makepot-zip'], {
		stdio: 'inherit',
		shell: true
	});

	releaseProcess.on('exit', (code) => {
		buildJS.kill();
	});
});


