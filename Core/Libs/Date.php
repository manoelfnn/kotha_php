<?php
namespace Core\Libs;

class Date {

	public static function weekDayName($_weekDay) {
		$names = [
			"Domingo",
			"Segunda-feira",
			"Terça-feira",
			"Quarta-feira",
			"Quinta-feira",
			"Sexta-feira",
			"Sábado"
		];
		return $names[$_weekDay];
	}

	public static function timeAgo($date) {
		$timestamp = strtotime($date);

		$strTime = array(
			"segundo",
			"minuto",
			"hora",
			"dia",
			"mês",
			"ano"
		);
		$strTimes = array(
			"segundos",
			"minutos",
			"horas",
			"dias",
			"meses",
			"anos"
		);
		$length = array(
			"60",
			"60",
			"24",
			"30",
			"12",
			"10"
		);

		$currentTime = time();
		if ($currentTime >= $timestamp) {
			$diff = time() - $timestamp;
			for ($i = 0; $diff >= $length[$i] && $i < count($length) - 1; $i ++) {
				$diff = $diff / $length[$i];
			}

			$diff = round($diff);
			return "há " . $diff . " " . ($diff > 1 ? $strTimes[$i] : $strTime[$i]) . "";
		}
	}

	public static function singleDate($_date) {
		$_date = strtotime($_date);
		$m = [
			"Jan",
			"Fev",
			"Mar",
			"Abr",
			"Mai",
			"Jun",
			"Jul",
			"Ago",
			"Set",
			"Out",
			"Nov",
			"Dez"
		];
		return date("d", $_date) . " " . $m[date("m", $_date) - 1] . (date("Y") != date("Y", $_date) ? " " . date("y", $_date) : "");
	}

	/**
	 * Date range
	 *
	 * @param
	 *        	$first
	 * @param
	 *        	$last
	 * @param string $step
	 * @param string $format
	 * @return array
	 */
	public static function dateRange($first, $last, $step = '+1 day', $format = 'Ymd') {
		$dates = [];
		$current = strtotime($first);
		$last = strtotime($last);

		while ($current <= $last) {

			$dates[] = date($format, $current);
			$current = strtotime($step, $current);
		}

		return $dates;
	}

	/**
	 * Number of days between two dates.
	 *
	 * @param string $dt1
	 *        	First date
	 * @param string $dt2
	 *        	Second date
	 * @return int
	 */
	public static function daysBetween($dt1, $dt2) {
		return date_diff(date_create($dt2), date_create($dt1))->format('%a');
	}

	public static function daysRemaing($_future) {
		$now = strtotime("today");
		if ($now > strtotime($_future)) {
			return 0;
		} else {
			return date_diff(date_create("now"), date_create($_future))->format('%a');
		}
	}
	
	public static function daysRemaingBetweenDates($_begin, $_end) {
		$today = strtotime("today");
		if ($today >= strtotime($_end) || $today < strtotime($_begin)) {
			return 0;
		} else {
			return date_diff(date_create("today"), date_create($_end))->format('%a');
		}
	}
}