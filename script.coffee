domForm = document.getElementById 'mb-form'
domFormRoles = document.getElementById 'mb-form-roles'
domFormWell = document.getElementById 'mb-form-well'
domNote = jQuery '#mb-note'
domSubmit = jQuery '#mb-submit'
selectedRoles = []
selectedWell = ''

checkFormValidity = ->
  unless selectedWell?
    domSubmit.prop 'disabled', true
    domNote.hide()
    return false
  
  domSubmit.prop 'disabled', false
  domNote.show()
  return true

transformBool = (boolish) ->
  return true unless boolish?
  selected = true if boolish == "true"
  return !selected

domSubmit.on 'click', ->
  return false unless checkFormValidity()
  
  domFormRoles.value = selectedRoles.join()
  domFormWell.value = selectedWell
  domForm.submit()
  return

jQuery '.mb-role'
.each (_) ->
  jQuery this
  .on 'click', ->
    ele = jQuery this
    selected = transformBool ele.attr 'data-selected'
    
    if selected
      ele.addClass 'selected'
    else
      ele.removeClass 'selected'
     
    ele.attr 'data-selected', selected
    
    id = ele.attr 'id'
    if selected
      selectedRoles.push id
    else
      i = selectedRoles.indexOf id
      selectedRoles.splice i, 1 if i != -1

jQuery '.mb-well'
.each (_) ->
  jQuery this
  .on 'click', ->
    ele = jQuery this
    
    # 'disable' all well elements
    jQuery '.mb-well'
    .each (_) ->
      ele2 = jQuery this
      ele2.attr 'data-selected', false
      ele2.removeClass 'selected'
      return
    
    # 'enable' this element
    selectedWell = ele.attr 'id'
    ele.attr 'data-selected', true
    ele.addClass 'selected'
    checkFormValidity()
    return
  
  return
