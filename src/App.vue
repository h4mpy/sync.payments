<template>
	<div id="app">
		<!--<router-view />-->
		<Main></Main>
	</div>
</template>
<style>
@import "./assets/styles/bootstrap-reboot.min.css";
@import "./assets/fonts/opensans/ui.font.opensans.css";
@import "./assets/styles/ui.buttons.css";
@import "./assets/styles/ui.alerts.css";
html {
	height: auto;
}
body {
	color: #000;
	font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
	font-size: 14px;
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
	width: auto;
	height: auto;
	margin: 0;
	padding: 0;
	background: #f9fafb;
}
form {
	margin: 0;
	padding: 0;
}
a {
	color: #2067b0;
	text-decoration: none;
	-webkit-transition: border-bottom-color 0.2s linear;
	transition: border-bottom-color 0.2s linear;
}
a:hover {
	color: #2067b0;
	text-decoration: none;
}
#app {
	max-width: 612px;
	margin: 50px auto;
	min-height: 100px;
	position: relative;
}
.loading {
	color: #424956;
	font: 15px/17px "Helvetica Neue", Helvetica, Arial, sans-serif;
	opacity: 0.5;
}
.form-check {
	position: relative;
	display: block;
	padding-left: 1.25rem;
}
.form-check-input {
	position: absolute;
	margin-top: 0.3rem;
	margin-left: -1.25rem;
}
.form-check-input:disabled ~ .form-check-label {
	color: #6c757d;
}
.form-group .form-check-label {
	margin-bottom: 0;
	color: #424956;
	font: 14px/25px "Helvetica Neue", Helvetica, Arial, sans-serif;
}
.info-widget {
	position: relative;
	display: block;
	margin-bottom: 5px;
	padding: 13px 10px;
	border: 1px solid #edeef0;
	border-radius: 1px;
	background-color: #fdfdfd;
	transition: 160ms all ease;
}
.info-widget__image {
	display: block;
	overflow: hidden;
	width: 30px;
	height: 30px;
	border-radius: 50%;
	background: #535c6a
		url("data:image/svg+xml,%3Csvg xmlns:xlink='http://www.w3.org/1999/xlink' width='18' height='18' viewBox='0 0 18 18' version='1.1' xmlns='http://www.w3.org/2000/svg'%3E%3Cstyle%3E.arrow %7B-moz-transform-origin: .5 0;-ms-transform-origin: .5 0;transform-origin: .5 0;%7D%3C/style%3E%3Ccircle fill='none' r='8.5' cx='9' cy='9' stroke='%23fff' stroke-width='1'/%3E%3Crect fill='%23fff' width='6px' height='1px' x='8.5' y='8.5'/%3E%3Cg width='19' height='19' x='0' y='0' fill='none' xmlns='http://www.w3.org/2000/svg' class='arrow'%3E%3Crect fill='%23fff' width='1' height='7' x='8.5' y='9'/%3E%3CanimateTransform attributeType='xml' attributeName='transform' type='rotate' from='0 9 9' to='360 9 9' dur='1s' repeatCount='indefinite'/%3E%3C/g%3E%3C/svg%3E");
	background-size: 18px;
	background-position: center;
	background-repeat: no-repeat;
}
.info-widget__text {
	position: absolute;
	top: 50%;
	right: 10px;
	left: 50px;
	transform: translateY(-50%);
}
.info-widget__title,
.info-widget__description {
	display: block;
	overflow: hidden;
	width: 100%;
	vertical-align: middle;
	text-overflow: ellipsis;
	white-space: nowrap;
	-o-text-overflow: ellipsis;
}
.info-widget__description {
	color: #80868e;
	font: 12px/16px "Helvetica Neue", Helvetica, Arial, sans-serif;
}
.pseudo {
	display: inline-block;
	margin-right: 15px;
	margin-bottom: 2px;
	border-bottom: 1px dashed;
	color: #80868e;
	text-decoration: none;
	white-space: nowrap;
	font: 13px/15px "Helvetica Neue", Helvetica, Arial, sans-serif;
	cursor: pointer;
	transition: 220ms all ease;
}
.pseudo:hover {
	color: #333;
}
.form-control {
	display: block;
	padding: 10px 9px;
	min-height: 39px;
	max-width: 100%;
	width: 100%;
	outline: 0;
	border: 1px solid #c4c7cc;
	border-radius: 1px;
	background-color: #fff;
	color: #424956;
	font: 15px/17px "Helvetica Neue", Helvetica, Arial, sans-serif;
	transition: 220ms all ease;
}
.form-control_small {
	display: inline-block;
	width: 100px;
}
.form-group {
	margin-bottom: 20px;
}
.form-group label {
	display: block;
	margin-bottom: 5px;
	padding-right: 20px;
	color: #a3a9b1;
	font: 12px/14px "Helvetica Neue", Helvetica, Arial, sans-serif;
}
.form-control:hover,
.form-control:focus {
	border-color: #64a6f3;
}
.form-control.is-invalid {
	border-color: #dc3545;
}
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
	-webkit-appearance: none;
	margin: 0;
}
.payment {
	position: relative;
	display: block;
	margin-bottom: 10px;
	padding: 13px 10px;
	border: 1px solid #edeef0;
	border-radius: 1px;
	background-color: #fdfdfd;
	transition: 160ms all ease;
}
.add-payment__actions {
	color: #424956;
}
.payments__holder + .add-payment__actions {
	padding-top: 20px;
}
.payments__holder .payment:last-child {
	margin-bottom: 0;
}
.payment__title {
	color: #a3a9b1;
	font: 12px/14px "Helvetica Neue", Helvetica, Arial, sans-serif;
}
.payment__sum {
	font: 18px/20px "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
}
.payment__head {
	padding-bottom: 8px;
}
.payment__head,
.payment__details {
	display: flex;
	justify-content: space-between;
	align-items: center;
}
.payment__additional {
	font: 500 13px/18px "Helvetica Neue", Arial, Helvetica, sans-serif;
	margin-top: 8px;
	color: rgba(66, 73, 86, 0.8);
	display: flex;
}
.payment__additional-item {
	margin-left: 15px;
}
.payment__additional-item:first-child {
	margin-left: 0;
}
.payment__additional-item .pseudo {
	margin: 0;
}
.payment__errors {
	margin-top: 10px;
}
.payment__errors .ui-alert:last-child {
	margin-bottom: 0;
}
.payment__actions {
	color: #a3a9b1;
	font: 12px/14px "Helvetica Neue", Helvetica, Arial, sans-serif;
	margin-top: -2px;
}
.payment__actions .pseudo {
	margin-right: 0;
	margin-bottom: 0;
	color: #a3a9b1;
	font: 12px/14px "Helvetica Neue", Helvetica, Arial, sans-serif;
}
.payment__edit {
	display: inline-block;
	padding-right: 5px;
	padding-left: 5px;
	width: 15px;
	height: 20px;
	vertical-align: middle;
	opacity: 0;
	cursor: pointer;
	transition: 220ms all ease;
	margin-top: -3px;
	margin-bottom: -5px;
}
.payment:hover .payment__edit {
	opacity: 0.4;
}
.payment .payment__edit:hover {
	opacity: 1;
}
.payment__form {
	margin-top: 15px;
}
.delete-icon {
	display: inline-block;
	opacity: 0;
	transition: 220ms all ease;
	margin-top: -4px;
	cursor: pointer;
}
.payment:hover .delete-icon {
	opacity: 0.4;
}
.payment .delete-icon:hover {
	opacity: 0.8;
}
.setlink {
	border-bottom: 1px dashed rgba(0, 0, 0, 0.15);
	cursor: pointer;
}
.label {
	display: inline-block;
	min-height: 18px;
	padding: 2px 15px;
	border-radius: 11px;
	font: bold 12px/18px "Helvetica Neue", Helvetica, Arial, sans-serif;
	background-color: #dde5e8;
	color: #6e7273;
}
.label_success {
	background-color: #9ece00;
	color: #fff;
}
.label_error {
	background-color: #ff5752;
	color: #fff;
}
.important {
	color: #ff5752;
}
.confirm .pseudo {
	margin-left: 7px;
}
.fade-enter-active,
.fade-leave-active {
	transition: opacity 0.05s;
}
.fade-enter,
.fade-leave-to {
	opacity: 0;
}
.sending:after {
	overflow: hidden;
	display: inline-block;
	vertical-align: bottom;
	-webkit-animation: ellipsis steps(4, end) 900ms infinite;
	animation: ellipsis steps(4, end) 900ms infinite;
	content: "\2026";
	width: 0;
}
.link,
.link:hover {
	text-decoration: underline;
}
@keyframes ellipsis {
	to {
		width: 1.25em;
	}
}
</style>
<script>
import Main from "./views/Main";
export default {
	components: { Main }
};
</script>
