# php Calendar Stub

### Генерация объекта даты начала или окончания периода, заданного по названию.

Решает проблему точных календарных дат в DateTime, при которой, например,
значение `(new DateTime('2017-01-31'))->modify('+1 month')->format('Y-m-d')` равно "2017-03-03"
а не "2017-02-28".


#### Установка
```bash
$ composer require phphleb/calendar-stub
```
#### Использование

```php
use Phphleb\CalendarStub\DateNameToPeriodConverter;
$converter = new DateNameToPeriodConverter();

// Получение даты за календарный месяц назад от текущего времени.
// Возможные значения периода: 'day', 'week', 'month', 'quarter', 'year', 'all'
// Период может быть кратным, например '2 months'.
// Методы getStartDate и getEndDate возвращают объект DateTime.
$date = $converter
        ->setPeriodName('month')
        ->getStartDate()
        ->format('Y-m-d H:i:s');

// Получение даты два календарных месяца назад от текущего времени.
$date = $converter
        ->setPeriodName('2 months')
        ->getStartDate()
        ->format('Y-m-d H:i:s');

// Получение даты два календарных месяца назад от заданного времени (28го февраля 2017).
$endPeriod = new DateTime('2017-02-28 00:00::00');
$date = $converter
        ->setPeriodName('2 months')
        ->setEndDate($endPeriod)
        ->getStartDate()
        ->format('Y-m-d H:i:s');

// При задании периода "all" ничего не рассчитывается, так как возвращается или
// start date от начала UNIX Time, так и уже установленная end date или текущее время.
$date = $converter
        ->setPeriodName('all')
        ->getEndDate()
        ->format('Y-m-d H:i:s');
```

