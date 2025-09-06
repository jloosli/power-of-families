import { exec, spawn } from 'child_process';
import { promisify } from 'util';
import { ChildProcess } from 'child_process';

const execAsync = promisify(exec);
let npmProcess: ChildProcess | null = null;

async function isDockerRunning() {
	try {
		const { stdout } = await execAsync('docker ps | grep pof_wordpress');
		return stdout.includes('pof_wordpress');
	} catch {
		return false;
	}
}

async function isNpmRunning() {
	try {
		const { stdout } = await execAsync(
			'ps aux | grep "npm run start" | grep -v grep'
		);
		return stdout.length > 0;
	} catch {
		return false;
	}
}

async function startNpm() {
	if (await isNpmRunning()) {
		console.log('npm process already running');
		return;
	}

	console.log('Starting npm run start...');
	npmProcess = spawn('npm', ['run', 'start'], {
		stdio: 'inherit',
		detached: true,
	});

	// Wait for the process to start
	await new Promise((resolve) => setTimeout(resolve, 5000));
}

async function globalSetup() {
	const dockerRunning = await isDockerRunning();
	if (!dockerRunning) {
		console.log('Starting WordPress Docker container...');
		await execAsync('docker-compose up -d wordpress');
		// Wait for WordPress to be ready
		await new Promise((resolve) => setTimeout(resolve, 10000));
	}

	await startNpm();
}

async function globalTeardown() {
	if (npmProcess) {
		// Kill the npm process and its children
		process.kill(-npmProcess.pid!);
		npmProcess = null;
	}
}

export { globalSetup as default, globalTeardown };
