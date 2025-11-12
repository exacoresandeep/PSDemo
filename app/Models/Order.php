<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\VehicleCategory;
use Carbon\Carbon;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_type',
        'customer_type_id',
        'order_category',
        'dealer_visit_id',
        'influencer_visit_id',
        'lead_id',
        'dealer_id',
        'product_id',
        'dealer_flag_order',
        'send_for_approval',
        'send_for_approval_by',
        'reason_for_rejection',
        'payment_terms_id',
        'credit_days',
        'advance_amount',
        'payment_date',
        'utr_number',
        'attachment',
        'aashiyana_attachment',   
        'advance_attachment',
    	'billing_date',
    	'delivery_date',
        'amount',
        'total_amount',
        'additional_information',
        'status',
        'source',
        'accepted_time',
        'rejected_time',
        'dispatched_time',
        'intransit_time',
        'delivered_time',
        'vehicle_category_id',
        'vehicle_type_id',
        'vehicle_number',
        'driver_name',
        'driver_phone',
        'invoice_number',
        'invoice_date',
        'invoice_quantity',
        'invoice_total',
        'created_by_dealer',
    	'created_by',
    	'notification_status',
    	'scheme',
        'order_approved',
        'order_approved_by',
        'order_payment_terms',
        'order_remarks',
        'vehicle_status',
        'vehicle_remarks',
    ];

    protected $casts = [
        'advance_amount' => 'float',
        'amount' => 'float',
        'total_amount' => 'float',
        'accepted_time' => 'datetime',
        'rejected_time' => 'datetime',
        'dispatched_time' => 'datetime',
        'intransit_time' => 'datetime',
        'delivered_time' => 'datetime',
        'attachment' => 'array',
        'aashiyana_attachment' => 'array',  
        'advance_attachment' => 'array',
        'invoice_date' => 'date',
        
    ];
    public function getBillingDateAttribute($value)
    {
        return $this->formatDateValue($value);
    }

    public function getDeliveryDateAttribute($value)
    {
        return $this->formatDateValue($value);
    }

    public function getPaymentDateAttribute($value)
    {
        return $this->formatDateValue($value);
    }

   protected function formatDateValue($value)
    {
        if (empty($value)) {
            return null;
        }
    
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value)->format('d/m/Y');
        }
    
       
        if (preg_match('/^\d{2}[\/-]\d{2}[\/-]\d{4}$/', $value)) {
            return str_replace('-', '/', $value);
        }
    
        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Exception $e) {
            return $value;
        }
    }


    

    public function orderType()
    {
        return $this->belongsTo(OrderType::class, 'order_type');
    }

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerms::class, 'payment_terms_id');
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

// This is for dealer created orders

    public function dealers()
    {
        return $this->belongsTo(Dealer::class, 'created_by_dealer');
    }
    
// This is for dealer created orders

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Employee::class, 'created_by')->withTrashed();
    }
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
    public function vehicleCategory()
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_number', 'invoice_number');
    }
    public function sendForApprovalBy()
    {
        return $this->belongsTo(Employee::class, 'send_for_approval_by');
    }
    public function dealerAddress()
    {
        return $this->hasMany(DealerAddress::class, 'dealer_id', 'dealer_id');
    }
   
    

    
}
