<?php

/*
  // OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
  // all the essential functionalities required for any enterprise.
  // Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com

  // OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
  // the GNU General Public License as published by the Free Software Foundation; either
  // version 2 of the License, or (at your option) any later version.

  // OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
  // without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  // See the GNU General Public License for more details.

  // You should have received a copy of the GNU General Public License along with this program;
  // if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
  // Boston, MA  02110-1301, USA
 */

/**
 * Form class for apply leave
 */
class ApplyLeaveForm extends sfForm {

    public $leaveTypeList = array();

    /**
     * Configure ApplyLeaveForm
     *
     */
    public function configure() {

        $this->leaveTypeList = $this->getOption('leaveTypes');

        $this->setWidgets(array(
            'txtEmpID' => new sfWidgetFormInputHidden(),
            'txtEmpWorkShift' => new sfWidgetFormInputHidden(),
            'txtLeaveType' => new sfWidgetFormChoice(array('choices' => $this->leaveTypeList)),
            'txtFromDate' => new ohrmWidgetDatePicker(array(), array('id' => 'applyleave_txtFromDate')),
            'txtToDate' => new ohrmWidgetDatePicker(array(), array('id' => 'applyleave_txtToDate')),
            'txtComment' => new sfWidgetFormTextarea(),
            'txtFromTime' => new sfWidgetFormChoice(array('choices' => $this->getTimeChoices())),
            'txtToTime' => new sfWidgetFormChoice(array('choices' => $this->getTimeChoices())),
            'txtLeaveTotalTime' => new sfWidgetFormInput(array(), array('readonly' => 'readonly')),
        ));
        $inputDatePattern = sfContext::getInstance()->getUser()->getDateFormat();
        $this->setValidators(array(
            'txtEmpID' => new sfValidatorString(array('required' => true), array('required' => 'Employee Id is required')),
            'txtEmpWorkShift' => new sfValidatorString(array('required' => false)),
            'txtLeaveType' => new sfValidatorChoice(array('choices' => array_keys($this->leaveTypeList))),
            'txtFromDate' => new ohrmDateValidator(array('date_format' => $inputDatePattern, 'required' => true),
                    array('invalid' => 'Date format should be ' . $inputDatePattern)),
            'txtToDate' => new ohrmDateValidator(array('date_format' => $inputDatePattern, 'required' => true),
                    array('invalid' => 'Date format should be ' . $inputDatePattern)),
            'txtComment' => new sfValidatorString(array('required' => false, 'trim' => true, 'max_length' => 1000)),
            'txtFromTime' => new sfValidatorString(array('required' => false)),
            'txtToTime' => new sfValidatorString(array('required' => false)),
            'txtLeaveTotalTime' => new sfValidatorNumber(array('required' => false)),
        ));


        $this->setDefault('txtEmpID', $this->getEmployeeNumber());
        $this->setDefault('txtEmpWorkShift', $this->getWorkShiftLength());

        $this->widgetSchema->setNameFormat('applyleave[%s]');


        $this->validatorSchema->setPostValidator(new sfValidatorCallback(array('callback' => array($this, 'postValidation'))));
    }

    public static function setLeaveTypes($types) {
        self::$leaveTypeList = $types;
    }

    /**
     * Get Time Choices
     * @return unknown_type
     */
    private function getTimeChoices() {
        $startTime = strtotime("00:00");
        $endTime = strtotime("23:59");
        $interval = 60 * 15;
        $timeChoices = array();
        $timeChoices[''] = '';
        for ($i = $startTime; $i <= $endTime; $i+=$interval) {
            $timeVal = date('H:i', $i);
            $timeChoices[$timeVal] = $timeVal;
        }
        return $timeChoices;
    }

    /**
     * get Leave Request 
     * @return LeaveRequest
     */
    public function getLeaveRequest() {

        $posts = $this->getValues();
        $leaveRequest = new LeaveRequest();
        $leaveRequest->setLeaveTypeId($posts['txtLeaveType']);
        $leaveRequest->setDateApplied($posts['txtFromDate']);
        $leaveRequest->setLeavePeriodId($this->getLeavePeriod($posts['txtFromDate']));
        $leaveRequest->setEmpNumber($posts['txtEmpID']);
        $leaveRequest->setLeaveComments($posts['txtComment']);
        return $leaveRequest;
    }

    /**
     * Get Leave
     * @return Leave
     */
    public function createLeaveObjectListForAppliedRange() {
        $posts = $this->getValues();

        $leaveList = array();
        $from = strtotime($posts['txtFromDate']);
        $to = strtotime($posts['txtToDate']);

        for ($timeStamp = $from; $timeStamp <= $to; $timeStamp = $this->incDate($timeStamp)) {
            $leave = new Leave();

            $leaveDate = date('Y-m-d', $timeStamp);
            $isWeekend = $this->isWeekend($leaveDate);
            $isHoliday = $this->isHoliday($leaveDate);
            $isHalfday = $this->isHalfDay($leaveDate);
            $isHalfDayHoliday = $this->isHalfdayHoliday($leaveDate);



            $leave->setLeaveDate($leaveDate);
            $leave->setLeaveComments($posts['txtComment']);
            $leave->setLeaveLengthDays($this->calculateDateDeference($isWeekend, $isHoliday, $isHalfday, $isHalfDayHoliday));
            $leave->setStartTime(($posts['txtFromTime'] != '') ? $posts['txtFromTime'] : '00:00');
            $leave->setEndTime(($posts['txtToTime'] != '') ? $posts['txtToTime'] : '00:00');
            $leave->setLeaveLengthHours($this->calculateTimeDeference($isWeekend, $isHoliday, $isHalfday, $isHalfDayHoliday));
            $leave->setLeaveStatus($this->getLeaveRequestStatus($isWeekend, $isHoliday));

            array_push($leaveList, $leave);
        }
        return $leaveList;
    }

    /**
     * Post validation
     * @param $validator
     * @param $values
     * @return unknown_type
     */
    public function postValidation($validator, $values) {
        $errorList = array();

        $fromDateTimeStamp = strtotime($values['txtFromDate']);
        $toDateTimeStamp = strtotime($values['txtToDate']);

        $fromTimetimeStamp = strtotime($values['txtFromTime']);
        $toTimetimeStamp = strtotime($values['txtToTime']);

        if ($fromDateTimeStamp === FALSE)
            $errorList['txtFromDate'] = new sfValidatorError($validator, 'Invalid From date');

        if ($toDateTimeStamp === FALSE)
            $errorList['txtToDate'] = new sfValidatorError($validator, 'Invalid To date');

        if ((is_int($fromDateTimeStamp) && is_int($toDateTimeStamp)) && ($toDateTimeStamp - $fromDateTimeStamp) < 0)
            $errorList['txtFromDate'] = new sfValidatorError($validator, ' From Date should be a previous date to To Date');

        if (($values['txtFromDate'] == $values['txtToDate']) && (is_int($fromTimetimeStamp) && is_int($toTimetimeStamp)) && ($toTimetimeStamp - $fromTimetimeStamp) < 0)
            $errorList['txtFromTime'] = new sfValidatorError($validator, ' From time should be a previous time to To time');

        if (($values['txtFromDate'] == $values['txtToDate']) && empty($values['txtLeaveTotalTime']))
            $errorList['txtLeaveTotalTime'] = new sfValidatorError($validator, 'Total hours required');

        if (($values['txtFromDate'] == $values['txtToDate']) && ($values['txtLeaveTotalTime'] == 0 || $values['txtLeaveTotalTime'] > 24))
            $errorList['txtLeaveTotalTime'] = new sfValidatorError($validator, 'Invalid Total hours');
        if (count($errorList) > 0) {

            throw new sfValidatorErrorSchema($validator, $errorList);
        }

        $values['txtFromDate'] = date('Y-m-d', $fromDateTimeStamp);
        $values['txtToDate'] = date('Y-m-d', $toDateTimeStamp);
        $values['txtLeaveTotalTime'] = number_format($values['txtLeaveTotalTime'], 2);

        return $values;
    }

    /**
     * Calculate Date deference
     * @return int
     */
    public function calculateDateDeference($isWeekend, $isHoliday, $isHalfday, $isHalfDayHoliday) {
        $posts = $this->getValues();
        if ($isWeekend)
            $dayDeference = 0;
        elseif ($isHoliday) {
            if ($isHalfDayHoliday) {
                if ($posts['txtToDate'] == $posts['txtFromDate']) {
                    if ($posts['txtEmpWorkShift'] / 2 <= $posts['txtLeaveTotalTime'])
                        $dayDeference = 0.5;
                    else
                        $dayDeference = number_format($posts['txtLeaveTotalTime'] / $posts['txtEmpWorkShift'], 3);
                }else
                    $dayDeference = 0.5;
            }else
                $dayDeference = 0;
        }elseif ($isHalfday) {

            if ($posts['txtToDate'] == $posts['txtFromDate']) {
                if ($posts['txtEmpWorkShift'] / 2 <= $posts['txtLeaveTotalTime'])
                    $dayDeference = 0.5;
                else
                    $dayDeference = number_format($posts['txtLeaveTotalTime'] / $posts['txtEmpWorkShift'], 3);
            }else
                $dayDeference = 0.5;
        }else {
            if ($posts['txtToDate'] == $posts['txtFromDate'])
                $dayDeference = number_format($posts['txtLeaveTotalTime'] / $posts['txtEmpWorkShift'], 3);
            else
            //$dayDeference	=	floor((strtotime($posts['txtToDate'])-strtotime($posts['txtFromDate']))/86400)+1;
                $dayDeference = 1;
        }

        return $dayDeference;
    }

    /**
     * Calculate Applied Date range
     * @return int
     */
    public function calculateAppliedDateRange($leaveList) {
        $dateRange = 0;
        foreach ($leaveList as $leave) {
            $dateRange += $leave->getLeaveLengthDays();
        }
        return $dateRange;
    }

    /**
     * Calculate Date deference
     * @return int
     */
    public function calculateTimeDeference($isWeekend, $isHoliday, $isHalfday, $isHalfDayHoliday) {
        $posts = $this->getValues();
        if ($isWeekend) {
            $timeDeference = 0;
        } elseif ($isHoliday) {
            if ($isHalfDayHoliday) {
                if ($posts['txtToDate'] == $posts['txtFromDate']) {
                    if ($posts['txtEmpWorkShift'] / 2 <= $posts['txtLeaveTotalTime'])
                        $timeDeference = number_format($posts['txtEmpWorkShift'] / 2, 3);
                    else
                        $timeDeference = $posts['txtLeaveTotalTime'];
                }else
                    $timeDeference = number_format($posts['txtEmpWorkShift'] / 2, 3);
            }else
                $timeDeference = 0;
        }elseif ($isHalfday) {
            if ($posts['txtToDate'] == $posts['txtFromDate'] && $posts['txtLeaveTotalTime'] > 0) {
                if ($posts['txtEmpWorkShift'] / 2 <= $posts['txtLeaveTotalTime'])
                    $timeDeference = number_format($posts['txtEmpWorkShift'] / 2, 3);
                else
                    $timeDeference = $posts['txtLeaveTotalTime'];
            }else
                $timeDeference = number_format($posts['txtEmpWorkShift'] / 2, 3);
        }else {
            if ($posts['txtToDate'] == $posts['txtFromDate'])
                $timeDeference = $posts['txtLeaveTotalTime'];
            else
                $timeDeference = $this->getWorkShiftLength();
        }
        return $timeDeference;
    }

    /**
     *
     * @param $isWeekend
     * @return status
     */
    public function getLeaveRequestStatus($isWeekend, $isHoliday) {
        $status = Leave::LEAVE_STATUS_LEAVE_PENDING_APPROVAL;

        if ($isWeekend)
            $status = Leave::LEAVE_STATUS_LEAVE_WEEKEND;

        if ($isHoliday)
            $status = Leave::LEAVE_STATUS_LEAVE_HOLIDAY;

        return $status;
    }

    /**
     *
     * @param $day
     * @return boolean
     */
    public function isWeekend($day) {
        $workWeekService = new WorkWeekService();
        $workWeekService->setWorkWeekDao(new WorkWeekDao());

        return $workWeekService->isWeekend($day, true);
    }

    /**
     *
     * @param $day
     * @return boolean
     */
    public function isHalfDay($day) {
        $workWeekService = new WorkWeekService();
        $workWeekService->setWorkWeekDao(new WorkWeekDao());

        $holidayService = new HolidayService();

        //this is to check weekday half days
        $flag = $holidayService->isHalfDay($day);
        if (!$flag) {
            //this checks for weekend half day
            return $workWeekService->isWeekend($day, false);
        }
        return $flag;
    }

    /**
     *
     * @param $day
     * @return boolean
     */
    public function isHoliday($day) {
        $holidayService = new HolidayService();
        return $holidayService->isHoliday($day);
    }

    /**
     *
     * @param $day
     * @return boolean
     */
    public function isHalfdayHoliday($day) {
        $holidayService = new HolidayService();
        return $holidayService->isHalfdayHoliday($day);
    }

    /**
     * Get Employee number
     * @return int
     */
    private function getEmployeeNumber() {
        return $_SESSION['empID'];
    }

    /**
     * get work shift length
     * @return int
     */
    private function getWorkShiftLength() {

        $employeeService = new EmployeeService();
        $employeeWorkShift = $employeeService->getWorkShift($this->getEmployeeNumber());
        if ($employeeWorkShift != null) {

            return $employeeWorkShift->getWorkShift()->getHoursPerDay();
        }else
            return WorkShift::DEFAULT_WORK_SHIFT_LENGTH;
    }

    /**
     * Date increment
     *
     * @param int $timestamp
     */
    private function incDate($timestamp) {

        return strtotime("+1 day", $timestamp);
    }

    /**
     * Get Leave Period
     * @param $fromDate
     * @return unknown_type
     */
    private function getLeavePeriod($fromDate) {

        $leavePeriodService = new LeavePeriodService();
        $leavePeriodService->setLeavePeriodDao(new LeavePeriodDao());

        $leavePeriod = $leavePeriodService->getLeavePeriod(strtotime($fromDate));

        if ($leavePeriod != null)
            return $leavePeriod->getLeavePeriodId();
        else
            return null;
    }

    /**
     * check overlap leave request
     * @return unknown_type
     */
    public function isOverlapLeaveRequest() {
        $posts = $this->getValues();
        $leavePeriodService = new LeavePeriodService();
        $leavePeriodService->setLeavePeriodDao(new LeavePeriodDao());

        $leavePeriod = $leavePeriodService->getLeavePeriod(strtotime($posts['txtFromDate']));

        if ($leavePeriod != null) {
            if ($posts['txtToDate'] > $leavePeriod->getEndDate())
                return true;
        }

        return false;
    }

}
