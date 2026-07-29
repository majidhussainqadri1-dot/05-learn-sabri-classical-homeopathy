(function () {
	'use strict';

	function send(data) {
		data.append('action', 'slc_learning_action');
		data.append('nonce', slcLearning.nonce);
		return fetch(slcLearning.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data }).then(function (response) {
			return response.json().then(function (json) {
				if (!response.ok || !json.success) {
					throw new Error(json.data && json.data.message ? json.data.message : slcLearning.strings.failed);
				}
				return json.data;
			});
		});
	}

	function lessonId(element) {
		var owner = element.closest('[data-lesson-id]');
		return owner ? owner.dataset.lessonId : '';
	}

	function text(tag, value) {
		var node = document.createElement(tag);
		node.textContent = String(value);
		return node;
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.slc-form select[name="topic"]').forEach(function (select) {
			var box = select.form.querySelector('[data-slc-case]');
			if (!box) { return; }
			function update() {
				var enabled = select.value === 'patient-case-learning';
				box.hidden = !enabled;
				box.querySelectorAll('input,select,textarea').forEach(function (input) {
					if (input.name !== 'case_anonymized' && input.name !== 'case_consent' && input.name !== 'consent_source' && input.name !== 'consent_evidence' && input.name !== 'consent_scope') { return; }
					input.required = enabled;
					if (!enabled && input.type === 'checkbox') { input.checked = false; }
					if (!enabled && input.type !== 'checkbox') { input.value = ''; }
				});
			}
			select.addEventListener('change', update);
			update();
		});
	});

	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-slc-action]');
		if (!button) { return; }
		event.preventDefault();
		var data = new FormData();
		data.append('kind', button.dataset.slcAction);
		data.append('lessonId', lessonId(button));
		button.disabled = true;
		send(data).then(function (result) {
			button.textContent = result.label;
			button.classList.toggle('is-active', !!result.active);
			button.setAttribute('aria-pressed', result.active ? 'true' : 'false');
		}).catch(function (error) {
			window.alert(error.message);
			if (/log in/i.test(error.message)) { window.location.href = slcLearning.loginUrl; }
		}).finally(function () { button.disabled = false; });
	});

	document.addEventListener('submit', function (event) {
		var form = event.target.closest('[data-slc-quiz]');
		if (!form) { return; }
		event.preventDefault();
		var answers = [];
		form.querySelectorAll('fieldset').forEach(function (field) {
			var checked = field.querySelector('input:checked');
			answers.push(checked ? parseInt(checked.value, 10) : -1);
		});
		var data = new FormData();
		data.append('kind', 'quiz');
		data.append('lessonId', lessonId(form));
		data.append('answers', JSON.stringify(answers));
		var button = form.querySelector('button[type="submit"]');
		if (button) { button.disabled = true; }
		send(data).then(function (result) {
			var target = form.querySelector('[data-slc-quiz-result]');
			if (!target) { return; }
			target.replaceChildren();
			var heading = text('h3', slcLearning.strings.score + ': ' + result.score + '% (' + result.correct + '/' + result.total + ')');
			var list = document.createElement('ol');
			result.review.forEach(function (item) {
				var row = document.createElement('li');
				var strong = text('strong', (item.correct ? slcLearning.strings.correct : slcLearning.strings.review) + ': ');
				row.appendChild(strong);
				row.appendChild(document.createTextNode(String(item.answer) + ' — ' + String(item.explanation)));
				list.appendChild(row);
			});
			target.appendChild(heading);
			target.appendChild(list);
		}).catch(function (error) {
			window.alert(error.message);
		}).finally(function () { if (button) { button.disabled = false; } });
	});
}());
