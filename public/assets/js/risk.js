document.addEventListener('DOMContentLoaded', function () {
	// GeneralRisks
	setupRiskDropdown('riskDropdownBtn', 'riskDropdownMenu', 'riskDropdownSelected', 'เลือกความเสี่ยง');
	// MedicalRisks
	setupRiskDropdown('medicalRiskDropdownBtn', 'medicalRiskDropdownMenu', 'medicalRiskDropdownSelected', 'เลือกความเสี่ยงทางอายุรกรรม');
	// ObstetricRisks
	setupRiskDropdown('obstetricRiskDropdownBtn', 'obstetricRiskDropdownMenu', 'obstetricRiskDropdownSelected', 'เลือกความเสี่ยงทางสูติกรรม');
});

function setupRiskDropdown(btnId, menuId, selectedId, defaultText) {
	const btn = document.getElementById(btnId);
	const menu = document.getElementById(menuId);
	const selected = document.getElementById(selectedId);

	if (!btn || !menu || !selected) return;

	// Toggle dropdown
	btn.onclick = function (e) {
		e.stopPropagation();
		menu.classList.toggle('hidden');
	};
	// Close dropdown when clicking outside
	document.addEventListener('click', function (e) {
		if (!menu.classList.contains('hidden')) {
			menu.classList.add('hidden');
		}
	});
	// Show selected risks
	menu.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
		cb.addEventListener('change', function () {
			const checked = Array.from(menu.querySelectorAll('input[type="checkbox"]:checked'))
				.map(x => x.nextElementSibling.textContent.trim());
			selected.textContent = checked.length ? checked.join(', ') : defaultText;
		});
	});
	// Initialize selected risks
	const checked = Array.from(menu.querySelectorAll('input[type="checkbox"]:checked'))
		.map(x => x.nextElementSibling.textContent.trim());
	selected.textContent = checked.length ? checked.join(', ') : defaultText;
}

function toggleDropdown(id) {
	// Close all dropdowns except the one being toggled
	[
		'generalRiskDropdown', 'medicalRiskDropdown', 'obstetricRiskDropdown',
		'addGeneralRiskDropdown', 'addMedicalRiskDropdown', 'addObstetricRiskDropdown'
	].forEach(function (dropId) {
		if (dropId !== id) {
			const el = document.getElementById(dropId);
			if (el) el.classList.add('hidden');
		}
	});
	var el = document.getElementById(id);
	if (el) {
		el.classList.toggle('hidden');
	}
}

// Close dropdowns when clicking outside
document.addEventListener('click', function (e) {
	[
		'generalRiskDropdown', 'medicalRiskDropdown', 'obstetricRiskDropdown',
		'addGeneralRiskDropdown', 'addMedicalRiskDropdown', 'addObstetricRiskDropdown'
	].forEach(function (id) {
		var btn = document.querySelector('[onclick*="' + id + '"]');
		var dropdown = document.getElementById(id);
		if (dropdown && !dropdown.contains(e.target) && btn && !btn.contains(e.target)) {
			dropdown.classList.add('hidden');
		}
	});
});