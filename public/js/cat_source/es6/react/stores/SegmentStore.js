/*
 * TodoStore
 * Segment structure example
 * {
     "last_opened_segment":"61079",
     "sid":"60984",
     "segment":"INDIETRO",
     "segment_hash":"0a7e4ea10d93b636d9de15132300870c",
     "raw_word_count":"1.00",
     "internal_id":"P147242AB-tu19",
     "translation":"",
     "version":null,
     "original_target_provied":"0",
     "status":"NEW",
     "time_to_edit":"0",
     "xliff_ext_prec_tags":"",
     "xliff_ext_succ_tags":"",
     "warning":"0",
     "suggestion_match":"85",
     "source_chunk_lengths":[],
     "target_chunk_lengths":{
         "len":[0],
         "statuses":["DRAFT"]
     },
     "readonly":"false",
     "autopropagated_from":"0",
     "repetitions_in_chunk":"1",
     "has_reference":"false",
     "parsed_time_to_edit":["00","00","00","00"],
     "notes":null
 }
 */

var AppDispatcher = require('../dispatcher/AppDispatcher');
var EventEmitter = require('events').EventEmitter;
var SegmentConstants = require('../constants/SegmentConstants');
var assign = require('object-assign');

EventEmitter.prototype.setMaxListeners(0);




var SegmentStore = assign({}, EventEmitter.prototype, {

    _segments : {},

    /**
     * Update all
     */
    updateAll: function(segments, fid, where) {
        if ( this._segments[fid] && where === "before" ) {
            Array.prototype.unshift.apply( this._segments[fid], this.normalizeSplittedSegments(segments));
        } else if( this._segments[fid] && where === "after" ) {
            Array.prototype.push.apply( this._segments[fid], this.normalizeSplittedSegments(segments));
        } else {
            this._segments[fid] = this.normalizeSplittedSegments(segments);
        }
    },

    normalizeSplittedSegments: function(segments) {
        var newSegments = [];
        $.each(segments, function (index) {
            var splittedSourceAr = this.segment.split(UI.splittedTranslationPlaceholder);
            if(splittedSourceAr.length > 1) {
                var segment = this;
                var splitGroup = [];
                $.each(splittedSourceAr, function (i) {
                    splitGroup.push(segment.sid + '-' + (i + 1));
                });

                $.each(splittedSourceAr, function (i) {
                    var translation = segment.translation.split(UI.splittedTranslationPlaceholder)[i];
                    var status = segment.target_chunk_lengths.statuses[i];
                    var segData = {
                        autopropagated_from: "0",
                        has_reference: "false",
                        parsed_time_to_edit: ["00", "00", "00", "00"],
                        readonly: "false",
                        segment: splittedSourceAr[i],
                        segment_hash: segment.segment_hash,
                        sid: segment.sid + '-' + (i + 1),
                        split_group: splitGroup,
                        split_points_source: [],
                        status: status,
                        time_to_edit: "0",
                        translation: translation,
                        version: segment.version,
                        warning: "0"
                    };
                    newSegments.push(segData);
                    segData = null;
                });
            } else {
                newSegments.push(this);
            }

        });
        return newSegments;
    },
    getSegmentById(sid, fid) {
        return this._segments[fid].find(function (seg) {
            return seg.sid == sid;
        });

    },

    splitSegment(oldSid, newSegments, fid, splitGroup) {
        var currentSegments = this._segments[fid];
        var index = currentSegments.findIndex(function (segment, index) {
            return (segment.sid == oldSid);
        });
        if (index > -1) {
            newSegments.forEach(function (element) {
                element.split_group = splitGroup;
            });
            Array.prototype.splice.apply(currentSegments, [index, 1].concat(newSegments));
        } else {
            this.removeSplit(oldSid, newSegments, currentSegments);
        }
    },

    removeSplit(oldSid, newSegments, currentSegments) {
        var elementsToRemove = [];
        var indexes = [];
        currentSegments.map(function (segment, index) {
            if (segment.sid.split('-').length && segment.sid.split('-')[0] == oldSid){
                elementsToRemove.push(segment);
                indexes.push(index);
                return index;
            }
        });
        if (elementsToRemove.length) {
            elementsToRemove.forEach(function (seg) {
                currentSegments.splice(currentSegments.indexOf(seg), 1)
            });
            Array.prototype.splice.apply(currentSegments, [indexes[0], 0].concat(newSegments));
        }
    },

    setStatus(sid, fid, status) {
        var segment = this.getSegmentById(sid, fid);
        segment.status = status;
    },

    setSuggestionMatch(sid, fid, perc) {
        var segment = this.getSegmentById(sid, fid);
        segment.suggestion_match = perc.replace('%', '');
    },

    setPropagation(sid, fid, propagation, from) {
        var segment = this.getSegmentById(sid, fid);
        if (propagation) {
            segment.autopropagated_from = from;
        } else {
            segment.autopropagated_from = "0";
        }
    },
    replaceTranslation(sid, fid, translation) {
        var segment = this.getSegmentById(sid, fid);
        return segment.translation = this.removeLockTagsFromString(translation);
    },
    replaceSource(sid, fid, source) {
        var segment = this.getSegmentById(sid, fid);
        return segment.translation = this.removeLockTagsFromString(source);
    },
    removeLockTagsFromString(str) {
        return str.replace(/<span contenteditable=\"false\" class=\"locked[^>]*\>(.*?)<\/span\>/gi, "$1");
    },

    getAllSegments: function () {
        var result = [];
        $.each(this._segments, function(key, value) {
            result = result.concat(value);
        });
        return result;
    },
    emitChange: function(event, args) {
        this.emit.apply(this, arguments);
    }
});


// Register callback to handle all updates
AppDispatcher.register(function(action) {

    switch(action.actionType) {
        case SegmentConstants.RENDER_SEGMENTS:
            SegmentStore.updateAll(action.segments, action.fid);
            SegmentStore.emitChange(action.actionType, SegmentStore._segments[action.fid], action.fid);
            break;
        case SegmentConstants.ADD_SEGMENTS:
            SegmentStore.updateAll(action.segments, action.fid, action.where);
            SegmentStore.emitChange(SegmentConstants.RENDER_SEGMENTS, SegmentStore._segments[action.fid], action.fid);
            break;
        case SegmentConstants.SPLIT_SEGMENT:
            SegmentStore.splitSegment(action.oldSid, action.newSegments, action.fid, action.splitGroup);
            SegmentStore.emitChange(action.actionType, SegmentStore._segments[action.fid], action.splitGroup, action.fid);
            break;
        case SegmentConstants.HIGHLIGHT_EDITAREA:
            SegmentStore.emitChange(action.actionType, action.id);
            break;
        case SegmentConstants.ADD_SEGMENT_CLASS:
            SegmentStore.emitChange(action.actionType, action.id, action.newClass);
            break;
        case SegmentConstants.REMOVE_SEGMENT_CLASS:
            SegmentStore.emitChange(action.actionType, action.id, action.className);
            break;
        case SegmentConstants.SET_SEGMENT_STATUS:
            SegmentStore.setStatus(action.id, action.fid, action.status);
            SegmentStore.emitChange(SegmentConstants.SET_SEGMENT_STATUS, action.id, action.status);
            break;
        case SegmentConstants.UPDATE_ALL_SEGMENTS:
            SegmentStore.emitChange(SegmentConstants.UPDATE_ALL_SEGMENTS);
            break;
        case SegmentConstants.SET_SEGMENT_HEADER:
            SegmentStore.setSuggestionMatch(action.id, action.fid, action.perc);
            SegmentStore.emitChange(SegmentConstants.SET_SEGMENT_PROPAGATION, action.id, false);
            SegmentStore.emitChange(action.actionType, action.id, action.perc, action.className, action.createdBy);
            break;
        case SegmentConstants.HIDE_SEGMENT_HEADER:
            SegmentStore.emitChange(SegmentConstants.SET_SEGMENT_PROPAGATION, action.id, false);
            SegmentStore.emitChange(action.actionType, action.id, action.fid);
            break;
        case SegmentConstants.SET_SEGMENT_PROPAGATION:
            SegmentStore.setPropagation(action.id, action.fid, action.propagation, action.from);
            SegmentStore.emitChange(action.actionType, action.id, action.propagation);
            break;
        case SegmentConstants.PROPAGATE_TRANSLATION:
            SegmentStore.emitChange(action.actionType, action.id);
            break;
        case SegmentConstants.REPLACE_TRANSLATION:
            var trans = SegmentStore.replaceTranslation(action.id, action.fid, action.translation);
            SegmentStore.emitChange(action.actionType, action.id, trans);
            break;
        case SegmentConstants.REPLACE_SOURCE:
            var source = SegmentStore.replaceSource(action.id, action.fid, action.source);
            SegmentStore.emitChange(action.actionType, action.id, source);
            break;
        case SegmentConstants.ADD_EDITAREA_CLASS:
            SegmentStore.emitChange(action.actionType, action.id, action.className);
            break;
        case SegmentConstants.REGISTER_TAB:
            SegmentStore.emitChange(action.actionType, action.tab, action.visible, action.open);
            break;
        case SegmentConstants.CREATE_FOOTER:
            SegmentStore.emitChange(action.actionType, action.sid);
            break;
        case SegmentConstants.SET_CONTRIBUTIONS:
            SegmentStore.emitChange(action.actionType, action.sid, action.matches, action.fieldTest);
            break;
        case SegmentConstants.CHOOSE_CONTRIBUTION:
            SegmentStore.emitChange(action.actionType, action.sid, action.index);
            break;
        case SegmentConstants.RENDER_GLOSSARY:
            SegmentStore.emitChange(action.actionType, action.sid, action.matches);
            break;
        default:
    }
});

module.exports = SegmentStore;