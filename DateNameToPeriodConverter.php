<?php

declare(strict_types=1);


namespace Phphleb\CalendarStub;

use DateTime;
use ErrorException;

/**
 * Обработка календарных названий дат для получения периода.
 */
class DateNameToPeriodConverter
{
    private const TIME_SECTIONS = ['day', 'week', 'month', 'quarter', 'year', 'all'];

    private ?string $parameter;

    private ?DateTime $endDate = null;

    private ?DateTime $startDate = null;

    /**
     * Параметр инициализации (период) может быть в формате:
     * "day", "week", "month", "quarter", "year"
     * После чего устанавливается startDate или endDate
     * и получается endDate или startDate соответственно.
     * Нет смысла устанавливать обе даты, так как мы получаем период и так.
     *
     * Так же можно произвольно указать период:
     * "14 days" или "2 weeks", "2 years", "1 year" и т.д.
     *
     * Переданное значение периода "all" устанавливает start date
     * в "начало времён" UNIX, то есть означает "за всё время".
     */
    public function __construct(string $parameter = null)
    {
        $this->parameter = $parameter;
    }

    /**
     * Устанавливает период для расчётов, аналогично конструктору.
     */
    public function setPeriodName(string $parameter): self
    {
        $this->parameter = $parameter;

        return $this;
    }

    /**
     * Устанавливает конечную дату, ДО которой будет вычислен период.
     * setStartDate() при этом устанавливать не нужно.
     */
    public function setEndDate(DateTime $date): self
    {
        $this->endDate = clone $date;

        return $this;
    }

    /**
     * Устанавливает начальную дату, ПОСЛЕ которой будет вычислен период.
     * setEndDate() при этом устанавливать не нужно.
     */
    public function setStartDate(DateTime $date): self
    {
        $this->startDate = clone $date;

        return $this;
    }

    /**
     * Возвращает объект конечной даты - start date c прибавлением периода или
     * установленное ранее значение end date.
     *
     * @throws ErrorException - при неправильном параметре.
     */
    public function getEndDate(): ?DateTime
    {
        if ($this->endDate || $this->parameter === 'all') {
            return $this->endDate ?? new DateTime('now');
        }
        if (!$this->startDate) {
            $this->startDate = DateTime::createFromFormat('U', '0');
        }

        return $this->getFromParseName($this->createParam($this->parameter), $this->startDate, true);
    }

    /**
     * Возвращает объект начальной даты - end date c вычитанием периода или
     * установленное ранее значение start date. В случае, если период выставлен в "all",
     * возвращает начальное время UNIX ("с начала времён"), т.е. относительное "за все время".
     *
     * @throws ErrorException - при неправильном параметре.
     */
    public function getStartDate(): ?DateTime
    {
        if ($this->parameter === 'all') {
            return DateTime::createFromFormat('U', '0');
        }
        if ($this->startDate) {
            return $this->startDate;
        }
        if (!$this->endDate) {
            $this->endDate = new DateTime('now');
        }

        return $this->getFromParseName($this->createParam($this->parameter), $this->endDate, false);
    }

    private function addMonths(DateTime $date, $monthToAdd): DateTime
    {
        $year = (int)$date->format('Y');
        $month = (int)$date->format('n');
        $day = (int)$date->format('d');
        $fullMonths = 0;
        if ($monthToAdd > 0) {
            if ($month + $monthToAdd > 12) {
                $fullMonths = (int)round($monthToAdd / 12);
                $year += $fullMonths;
            }
            $permanentMonths = ($monthToAdd - $fullMonths * 12);
            if ($month + $permanentMonths <= 12) {
                $month = $month + $permanentMonths;
            } else {
                $year += 1;
                $month = ($permanentMonths + $month) - 12;
            }
        } else {
            $fullMonths = (int)round(abs($monthToAdd) / 12);
            echo "fullMonth=$fullMonths";
            $year -= $fullMonths;
            $permanentMonths = (abs($monthToAdd) - $fullMonths * 12);
            echo "permanentMonths=$permanentMonths";
            if ($month > $permanentMonths) {
                $month = $month - $permanentMonths;
            } else {
                if ($month < $permanentMonths) {
                    $year -= 1;
                }
                $month = 12 - ($permanentMonths - $month);
            }
        }
        $month = intval($month) ?: 1;
        $isLastDay = (int)(clone $date)->modify('last day of')->format('d') === $day;
        if (!checkdate($month, $day, $year) || $isLastDay) {
            $monthName = date('F', strtotime("$year-$month-1"));
            $result = new DateTime("last day of $monthName $year");
        } else {
            $result = DateTime::createFromFormat('Y-n-d', $year . '-' . $month . '-' . $day);
        }
        $result->setTime(
            (int)$date->format('G'),
            (int)$date->format('i'),
            (int)$date->format('s'),
        );
        return $result;
    }

    /**
     * @throws ErrorException
     */
    private function getFromParseName(string $name, DateTime $date, bool $isAdd): ?DateTime
    {
        $parts = explode(' ', $name);
        $tag = $parts[1] ?? $name;
        if (!in_array($tag, self::TIME_SECTIONS)) {
            throw new ErrorException("Invalid parameter, must be from: " . implode(', ', self::TIME_SECTIONS));
        }
        if (count($parts) === 2) {
            $number = abs(intval($parts[0]));
        }
        $sign = $isAdd ? '+' : '-';
        switch ($tag) {
            case 'day':
                return $date->modify("$sign{$number} day") ?: null;
            case 'days':
                return $date->modify("$sign{$number} days") ?: null;
            case 'week':
                return $date->modify("$sign{$number} week") ?: null;
            case 'weeks':
                return $date->modify("$sign{$number} weeks") ?: null;
            case 'month':
            case 'months':
                return $this->addMonths($date, intval("$sign{$number}"));
            case 'quarter':
            case 'quarters':
                return $this->addMonths($date, intval("$sign{$number}") * 3);
            case 'year':
                return $date->modify("$sign{$number} year") ?: null;
            case 'years':
                return $date->modify("$sign{$number} years") ?: null;
        }
        return null;
    }

    private function createParam(string $name): string
    {
        return count(explode(' ', $name)) === 2 ? $name : "1 " . $name;
    }
}
